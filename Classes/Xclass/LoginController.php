<?php
declare(strict_types = 1);
namespace Toumoro\TmSaml\Xclass;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for rendering the login form
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class LoginController extends \TYPO3\CMS\Backend\Controller\LoginController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Checking, if we should perform some sort of redirection OR closing of windows.
     *
     * Do a redirect if a user is logged in
     *
     * @param ServerRequestInterface $request
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function checkRedirect(ServerRequestInterface $request): void
    {
        $backendUser = $this->getBackendUserAuthentication();

        if (empty($backendUser->user['uid'])) {
            return;
        }

        /*
         * If no cookie has been set previously, we tell people that this is a problem.
         * This assumes that a cookie-setting script (like this one) has been hit at
         * least once prior to this instance.
         */
        if (!isset($_COOKIE[BackendUserAuthentication::getCookieName()])) {
            if ($this->submitValue === 'setCookie') {
                /*
                 * we tried it a second time but still no cookie
                 * 26/4 2005: This does not work anymore, because the saving of challenge values
                 * in $_SESSION means the system will act as if the password was wrong.
                 */

                /*
                 * Allow session creation from saml redirection
                 */
                echo 'Redirection...';
                echo '<meta http-equiv="refresh" content="0;url=index.php" />';
                exit();
                //throw new \RuntimeException('Login-error: Yeah, that\'s a classic. No cookies, no TYPO3. ' .
                //    'Please accept cookies from TYPO3 - otherwise you\'ll not be able to use the system.', 1294586846);
            }
            // try it once again - that might be needed for auto login
            $this->redirectToURL = 'index.php?commandLI=setCookie';
        }
        $redirectToUrl = (string)($backendUser->getTSConfig()['auth.']['BE.']['redirectToURL'] ?? '');
        if (empty($redirectToUrl)) {
            // Based on the interface we set the redirect script
            $parsedBody = $request->getParsedBody();
            $queryParams = $request->getQueryParams();
            $interface = $parsedBody['interface'] ?? $queryParams['interface'] ?? '';
            switch ($interface) {
                case 'frontend':
                    $this->redirectToURL = '../';
                    break;
                case 'backend':
                    // (consolidate RouteDispatcher::evaluateReferrer() when changing 'main' to something different)
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                    $this->redirectToURL = (string)$uriBuilder->buildUriFromRoute('main');
                    break;
            }
        } else {
            $this->redirectToURL = $redirectToUrl;
            $interface = '';
        }
        // store interface
        $backendUser->uc['interfaceSetup'] = $interface;
        $backendUser->writeUC();

        $formProtection = FormProtectionFactory::get();
        if (!$formProtection instanceof BackendFormProtection) {
            throw new \RuntimeException('The Form Protection retrieved does not match the expected one.', 1432080411);
        }
        if ($this->loginRefresh) {
            $formProtection->setSessionTokenFromRegistry();
            $formProtection->persistSessionToken();
            $this->getDocumentTemplate()->JScode .= GeneralUtility::wrapJS('
				if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.LoginRefresh) {
					window.opener.TYPO3.LoginRefresh.startTask();
					window.close();
				}
			');
        } else {
            $formProtection->storeSessionTokenInRegistry();
            $this->redirectToUrl();
        }
    }

}
