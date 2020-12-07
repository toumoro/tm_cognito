<?php

defined('TYPO3_MODE') || die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_fetchUserIfNoSession'] = 1;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    $_EXTKEY,
    'auth',
    "Toumoro\\TmCognito\\Service\\AuthenticationService",
    array(
        'title' => 'Authentication service',
        'description' => 'Authentication service for Cognito.',
        'subtype' => 'getUserBE,authUserBE',
        'available' => true,
        'priority' => 80,
        'quality' => 80,
        'os' => '',
        'exec' => '',
        'className' => "Toumoro\\TmCognito\\Service\\AuthenticationService",
    )
);
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\Controller\\LogoutController'] = array(
    'className' => 'Toumoro\\TmCognito\\Xclass\\LogoutController'
 );