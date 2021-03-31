<?php

defined('TYPO3_MODE') || die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_fetchUserIfNoSession'] = true;
$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = true;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    $_EXTKEY,
    'auth',
    "Toumoro\\TmSaml\\Service\\AuthenticationService",
    array(
        'title' => 'Authentication service',
        'description' => 'Authentication service for Saml.',
        'subtype' => 'getUserBE,authUserBE,getUserFE,authUserFE',
        'available' => true,
        'priority' => 80,
        'quality' => 80,
        'os' => '',
        'exec' => '',
        'className' => "Toumoro\\TmSaml\\Service\\AuthenticationService",
    )
);
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\Controller\\LogoutController'] = array(
    'className' => 'Toumoro\\TmSaml\\Xclass\\LogoutController'
 );
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\Controller\\LoginController'] = array(
    'className' => 'Toumoro\\TmSaml\\Xclass\\LoginController'
 );
