<?php
defined('TYPO3_MODE') or die();

call_user_func(function() {
    if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Mail\MailMessage::class])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Mail\MailMessage::class] = [];
    }
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Mail\MailMessage::class] = [
        'className' => \Glowpointzero\SiteOperator\Mail\MailMessage::class
    ];
});
