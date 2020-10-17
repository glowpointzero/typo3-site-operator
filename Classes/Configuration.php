<?php
namespace Glowpointzero\SiteOperator;

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

class Configuration
{
    /**
     * Invookes the XClassing of the default / core fluid email
     * class. This is *not* done in ext_localconf, as some of
     * the sending takes place before ext_localconf.php is loaded
     * - for example the backend test email. :(
     */
    public static function enableAdvancedFluidEmails() {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Mail\FluidEmail::class] = [
            'className' => \Glowpointzero\SiteOperator\Mail\EmailMessage::class
        ];

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'][] = 'EXT:site_operator/Resources/Private/Layouts/Mail';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'][] = 'EXT:site_operator/Resources/Private/Partials/Mail';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][] = 'EXT:site_operator/Resources/Private/Templates/Mail';
    }
}
