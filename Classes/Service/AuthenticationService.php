<?php
namespace Toumoro\TmCognito\Service;

/***
 *
 * This file is part of the "tm_cognito" Extension for TYPO3 CMS by Toumoro.com.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Toumoro.com (Simon Ouellet)
 *
 ***/

require  \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tm_cognito').'Resources/Private/PHP/php-jwt/src/ExpiredException.php';
require  \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tm_cognito').'Resources/Private/PHP/php-jwt/src/JWK.php';
require  \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tm_cognito').'Resources/Private/PHP/php-jwt/src/JWT.php';


/**
 * thx to Causal\IgLdapSsoAuth Xavier Perseguers <xavier@causal.ch>
 */
class AuthenticationService extends \TYPO3\CMS\Sv\AuthenticationService {
    /**
     * Default constructor
     */
    public function __construct()
    {
        //get ext config
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tm_cognito'];
        $this->config = $config ? unserialize($config) : [];
    }


    public function getUser() {
        if ((TYPO3_MODE === 'BE') && ($this->config['active'])){
                $user = $this->valUser();
        }
        return $user;
    }

    /**
     * Authenticates a user (Check various conditions for the user that might invalidate its
     * authentication, e.g., password match, domain, IP, etc.).
     *
     * @param array $user Data of user.
     * @return int|false
     */
    public function authUser(array $user) {
        $status = 403;
         
        if (!$this->config['active']) {
            $status = 100;
        } else {
            $tmpUser = $this->valUser();
            var_dump($tmpUser);
            if ($tmpUser == false) {
                $status = 100;
            } else {
                if (isset($tmpUser['username'])) {
                    $status = 200;
                }
            }
        }
        
        //exit();
        return $status;

    }

    private function valUser() {

        $user = false;
        $lastUser = $this->getCookieData('LastAuthUser');
        $lastUserEscape = str_replace('.','_',$lastUser);
        if ($lastUser) {
            $jwks_json = file_get_contents('https://cognito-idp.'.$this->config['region'].'.amazonaws.com/'.$this->config['userPoolId'].'/.well-known/jwks.json');
            $jwk = \Firebase\JWT\JWK::parseKeySet($jwks_json);
            $idToken = $this->getCookieData('idToken',$lastUserEscape);
            $tks = explode('.', $idToken);

            list($headb64, $bodyb64, $cryptob64) = $tks;
            $jwt_header = json_decode(base64_decode($headb64),true);
            $jwt_body = json_decode(base64_decode($bodyb64),true);
            $key=$jwk[$jwt_header["kid"]];

            try {
                $decoded = \Firebase\JWT\JWT::decode($idToken, $key, array($jwt_header["alg"]));
            } catch(\Firebase\JWT\ExpiredException $e) {
                exit("expired");
            }
            $decoded_array = (array) $decoded;
            
            $user=$this->fetchUserRecord($decoded_array['cognito:username']);
            //print_r($decoded_array);
            //$user=$this->fetchUserRecord('dev');
            
            if ((!empty($this->config['cognitoRequiredGroup'])) && ($user)) {
                
                if (isset($decoded_array['cognito:groups'])) {
                    if (array_search($this->config['cognitoRequiredGroup'],$decoded_array['cognito:groups']) === FALSE) {
                        $user = false;
                    }
                }
            
            }
        }
        return $user;

    }

    private function getCookieData($key,$user=null) {
        $ret = false;
        $cookieKey = 'CognitoIdentityServiceProvider_'.$this->config['clientId'].'_';
        if ($user != null) {
            $cookieKey .= $user."_";
        }
        $cookieKey .= $key;
        if (!empty($_COOKIE[$cookieKey])) {
            $ret = $_COOKIE[$cookieKey];
        }
        return $ret;
        
    }

}
