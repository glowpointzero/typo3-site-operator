<?php
namespace Glowpointzero\SiteOperator\DataProvider;

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

use Glowpointzero\SiteOperator\Utility\FileSystemUtility;

class TemplateDataProviderHook
{

    public static function registerHook()
    {   
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']
            ['Core/TypoScript/TemplateService']['runThroughTemplatesPostProcessing'][1544358763] =
            self::class . '->addTypoScriptTemplate';
    }

    /**
    * Hooks into TemplateService to add a virtual TS template
    *
    * @param array $parameters
    * @param \TYPO3\CMS\Core\TypoScript\TemplateService $parentObject
    */
    public function addTypoScriptTemplate($parameters, \TYPO3\CMS\Core\TypoScript\TemplateService $parentObject)
    {
        $currentConstants = $parentObject->constants;
        $currentSetup = $parentObject->config;
        
        $packageKey = \Glowpointzero\SiteOperator\ProjectInstance::getSitePackageKey();
        $constantsFilePath = FileSystemUtility::getContextDependentFilePath(
            sprintf('EXT:%s/Configuration/TypoScript/', $packageKey),
            'constants',
            'typoscript'
        );
        $setupFilePath = FileSystemUtility::getContextDependentFilePath(
            sprintf('EXT:%s/Configuration/TypoScript/', $packageKey),
            'setup',
            'typoscript'
        );
        
        // Add a custom, fake 'sys_template' record
        $row = [
            'title' => sprintf('Virtual TS root template (%s)', self::class),
            'uid' => 'typo3-site-operator',
            'static_file_mode' => 1,
            'constants' =>
                '@import \'' . $constantsFilePath . '\'' . PHP_EOL
                . implode(PHP_EOL, $currentConstants) . PHP_EOL,
            'config' =>
                '@import \'' . $setupFilePath . '\'' . PHP_EOL
                . implode(PHP_EOL, $currentSetup) . PHP_EOL
        ];

        $parentObject->processTemplate(
            $row,
            'sys_' . $row['uid'],
            $parameters['absoluteRootLine'][0]['uid'],
            'sys_' . $row['uid']
        );

        // Though $parentObject->rootId is deprecated (and protected),
        // this needs to be set (as there are no alternatives yet).
        // One of the side-effects, if not set, is that the menu
        // rendering cannot determine the current/active states.
        $parentObject->rootId = $parameters['absoluteRootLine'][0]['uid'];
    }
}
