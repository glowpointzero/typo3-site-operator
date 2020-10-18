<?php
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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use \Glowpointzero\SiteOperator\ProjectInstance;
use \Glowpointzero\SiteOperator\Mail\EmailMessage;

/***
 * This is an example/dummy configuration file, demonstrating some
 * of the features of the glowpointzero/site-operator package.
 * I'd be a good idea to version this file in your project
 * repository and you might want to store it outside your web
 * root and include it in the 'typo3conf/AdditionalConfiguration.php'
 * file. P.e. using:
 *
 * include dirname(dirname(dirname(__FILE__))) . '/config/ConfigurationOverrides.php';
 */

$instanceName = 'My Company';
$maintainerEmailAddress = 'your-email@address.com';

/*
 * SITE OPERATOR
 */
// Initialize instance
ProjectInstance::initialize('site_package');
// Use template hook
\Glowpointzero\SiteOperator\DataProvider\TemplateDataProviderHook::registerHook();
// Set application version as a constant
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
    'application.version = ' . ProjectInstance::getApplicationVersion()
);

/*
 * SITE NAME
 */
$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = $instanceName;

/*
 * LANGUAGES
 */
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages'] = ['de'];

/*
 * E-MAIL
 */
$GLOBALS['TYPO3_CONF_VARS']['MAIL'] = array_merge(
    $GLOBALS['TYPO3_CONF_VARS']['MAIL'],
    [
        'defaultMailFromAddress' => 'noreply@' . GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'),
        'defaultMailFromName' => $instanceName,
        'defaultMailReplyToAddress' => 'noreply@' . GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'),
        'defaultMailReplyToName' => $instanceName
    ]
);

if (ProjectInstance::runsInApplicationContext(null, 'local')) {
    $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = array_merge(
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'],
        [
            'transport' => 'mbox',
            'transport_mbox_file' => Environment::getVarPath() . '/sent-mails.eml'
        ]
    );
}

// Implement advanced fluid emails
\Glowpointzero\SiteOperator\Configuration::enableAdvancedFluidEmails();

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'][] = sprintf('EXT:%s/Resources/Private/Layouts/Mail', ProjectInstance::getSitePackageKey());
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'][] = sprintf('EXT:%s/Resources/Private/Partials/Mail', ProjectInstance::getSitePackageKey());
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][] = sprintf('EXT:%s/Resources/Private/Templates/Mail', ProjectInstance::getSitePackageKey());

EmailMessage::setDefaultCssFilePath(sprintf('EXT:%s/Resources/Public/Css/email.css', ProjectInstance::getSitePackageKey()));
EmailMessage::addDefaultEmbeddable('logo', sprintf('EXT:%s/Resources/Public/Images/email-logo-all-sites.png', ProjectInstance::getSitePackageKey()));
EmailMessage::addDefaultEmbeddable('logo', sprintf('EXT:%s/Resources/Public/Images/email-logo-site-one.png', ProjectInstance::getSitePackageKey()), 'site-one');
EmailMessage::addDefaultEmbeddable('logo', sprintf('EXT:%s/Resources/Public/Images/email-logo-site-two.png', ProjectInstance::getSitePackageKey()), 'site-two');


/*
 * FRONTEND DEBUGGING
 */
if (ProjectInstance::runsInApplicationContext('Development')) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
}

/*
 * BACKEND SETTINGS & SKINNING
 */
$GLOBALS['TYPO3_CONF_VARS']['BE'] = array_merge(
    $GLOBALS['TYPO3_CONF_VARS']['BE'],
    [
        'debug' => true,
        'explicitADmode' => 'explicitDeny',
        'warning_email_addr' => $maintainerEmailAddress
    ]
);
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['backend'] = array_merge(
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['backend'],
    [
        'loginLogo' => sprintf('EXT:%s/Resources/Public/Images/logo-all-sites.svg', ProjectInstance::getSitePackageKey()),
        'backendLogo' => sprintf('EXT:%s/Resources/Public/Images/icon-all-sites.png', ProjectInstance::getSitePackageKey()),
        'loginBackgroundImage' => sprintf('EXT:%s/Resources/Public/Backend/LoginBackground.jpg', ProjectInstance::getSitePackageKey())
    ]
);
