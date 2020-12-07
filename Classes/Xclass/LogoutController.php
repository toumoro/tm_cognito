<?php
namespace Toumoro\TmCognito\Xclass;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for logging a user out.
 * Does not display any content, just calls the logout-function for the current user and then makes a redirect.
 */
class LogoutController extends  \TYPO3\CMS\Backend\Controller\LogoutController
{
    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     * This will be split up in an abstract controller once proper routing/dispatcher is in place.
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function logoutAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        
        $this->logout();
        
        $redirectUrl = 'https://'.$_SERVER['HTTP_HOST'].'/signout';

        //remove all cookie
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                setcookie($name, '', time()-1000);
                setcookie($name, '', time()-1000, '/');
            }
        }

        if (empty($redirectUrl)) {
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            $redirectUrl = (string)$uriBuilder->buildUriFromRoute('login', [], $uriBuilder::ABSOLUTE_URL);
        }
        return $response
            ->withStatus(303)
            ->withHeader('Location', GeneralUtility::locationHeaderUrl($redirectUrl));
    }

}