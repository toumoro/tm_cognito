<?php
namespace Toumoro\TmSaml\Service;


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***
 *
 * This file is part of the "Backend authentication with AWS Cognito" Extension for TYPO3 CMS by Toumoro.com.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Toumoro.com (Simon Ouellet)
 *
 ***/


/**
 * thx to Causal\IgLdapSsoAuth Xavier Perseguers <xavier@causal.ch>
 */
class AuthenticationService extends \TYPO3\CMS\Core\Authentication\AuthenticationService {

    private $settings = array();

    public function initAuth    (       $mode,  $loginData, $authInfo,  $pObj ) {
        $this->settings =  $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tm_saml'];
        parent::initAuth($mode,$loginData,$authInfo,$pObj);

    }

    public function getUser() {


        //if scheduler
	if ((\TYPO3\CMS\Core\Core\Environment::isCli()) || ($_SERVER['REMOTE_ADDR'] == '127.0.0.1')) {
            return array();
        }

        $this->tablename = 'fe_users';
        $this->tablenameGroup = 'fe_groups';
        $this->nameField = 'name';
        if ((TYPO3_MODE === 'BE')) {
            $this->tablename = 'be_users';
            $this->tablenameGroup = 'be_groups';
            $this->nameField = 'realName';
        }

        $user = $this->valUser();
        return $user;
    }

    /**
     * Authenticates a user (Check various conditions for the user that might invalidate its
     * authentication, e.g., password match, domain, IP, etc.).
     *
     * @param array $user Data of user.
     * @return int|false
     */

    public function authUser(array $user): int {
	if ((\TYPO3\CMS\Core\Core\Environment::isCli()) || ($_SERVER['REMOTE_ADDR'] == '127.0.0.1')) {
            return 200;
        }

        if (!empty($user)) {
            return 200;
        }
        return 100;
    }

    private function valUser() {
        $as = new \SimpleSAML\Auth\Simple('default-sp');
        $as->requireAuth();
        $attr = $as->getAttributes();
        //print_r($attr);
        $username = $attr[$this->settings['usernamePath']][0];
        $groups = $attr[$this->settings['groupPath']];

        //si invité
        if (empty($username)) {
            $username = $attr['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'][0];
        }
        //comma seperated
        $adminGroups  = explode(",",$this->settings[TYPO3_MODE.'AdminGroup']);
        //print_r($attr);

        //TODO createGroup
        //group validation
        $hasGroup = true;

        $displayName =$attr['http://schemas.microsoft.com/identity/claims/displayname'][0];

        if ((!empty($this->settings[TYPO3_MODE.'Group'])) && (!empty($groups))) {

            $validationgroups = explode(',',$this->settings[TYPO3_MODE.'Group']);
            $hasGroup  = false;
            $isAdmin = 0;
            foreach($validationgroups as $k => $g) {
                //0 id 1 groupname
                $groupMapping = explode(":",$g);

                //if admin
                if (!empty($adminGroups) && (TYPO3_MODE=="BE")) {
                    foreach($adminGroups as $keyG => $admg) {
                        $admGroupId = explode(":",$admg);
                        if (in_array($admGroupId[0],$groups) !== false) {
                            $isAdmin = true;
                        }
                    }
                }

                if (in_array($groupMapping[0],$groups) !== false) {
                    $hasGroup = true;
                    $data['usergroup'][] = $this->getGroup($groupMapping[1]);
                }
            }

        }
        if (!$hasGroup) {
            exit("403");
            return false;
        }


        $user = $this->getUserInfo($username);


        //if the user is not found, we create it.
        if(empty($user)) {
            $tableConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tablename);
            $data['tstamp'] = time();
            $data['username'] = $username;
            $data['pid'] = 1;
            $data[$this->nameField] = $displayName;
            $data['usergroup'] = implode(",",$data['usergroup']);
            if ((TYPO3_MODE == 'BE')  && ($isAdmin)) {
                $data['admin'] = 1;
            }
            if ((TYPO3_MODE == 'BE')  ) {
                $data['pid'] = 0;
                $data['options'] = 3;
            }


            $tableConnection->insert(
                $this->tablename,
                $data
            );

            $user = $this->getUserInfo($username);

        }

        return $user;



    }


    private function getUserInfo($username) {

        $ret = false;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tablename);

        $users = $queryBuilder
            ->select('*')
            ->from($this->tablename)
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )
            ->execute()
            ->fetchAll();
        if (!empty($users)) {
            $ret = $users[0];
        }
        return $ret;

    }


    private function getGroup($name) {
        $ret = false;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tablenameGroup);

        $group = $queryBuilder
            ->select('*')
            ->from($this->tablenameGroup)
            ->where(
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($name))
            )
            ->execute()
            ->fetchAll();
        if (!empty($group)) {
            $ret = $group[0];
            return $ret['uid'];
        } else {
            $queryBuilder->insert($this->tablenameGroup)->values([
                'title' => $name
            ])->execute();
            return $queryBuilder->getConnection()->lastInsertId();
        }

    }


}
