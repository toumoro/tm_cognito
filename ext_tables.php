<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tm_cognito', 'Configuration/TypoScript', 'tm_cognito');

//        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_tmcognito_domain_model_cognitouser', 'EXT:tm_cognito/Resources/Private/Language/locallang_csh_tx_tmcognito_domain_model_cognitouser.xlf');
//        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_tmcognito_domain_model_cognitouser');

    }
);
