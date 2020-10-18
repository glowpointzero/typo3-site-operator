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

use TYPO3\CMS\Core\Core\Environment;

class ProjectInstance
{
    protected static $sitePackageKey;
    protected static $siteIdentifier;
    protected static $mainApplicationContext;
    protected static $applicationSubContext;
    protected static $applicationVersion;
    
    /**
     * Initialises this class. Should only be done once per request.
     *
     * @todo Maybe throw an exception, if somebody tries to re-initialize this class?
     * @param string $sitePackageKey
     */
    public static function initialize(
        $sitePackageKey
     ) {
        self::$sitePackageKey = $sitePackageKey;

        $splitContext = explode('/', \TYPO3\CMS\Core\Core\Environment::getContext());
        $currentMainContext = $splitContext[0];
        $currentSubContext = (count($splitContext) > 1) ? $splitContext[1] : '';

        self::$mainApplicationContext = $currentMainContext ?: '';
        self::$applicationSubContext = $currentSubContext ?: '';
        self::$applicationVersion = self::retrieveApplicationVersion();
    }

    /*
     * Returns the site package (extension) key.
     */
    public static function getSitePackageKey()
    {
        return self::$sitePackageKey;
    }

    /**
     * Retrieves current site identifier.
     * We don't do this in the constructor, as at the time
     * the ProjectInstance is initialized, there might not
     * be a TyposcriptFrontendController yet.
     *
     * @return string|null
     * @throws \Exception
     */
    public static function getSiteIdentifier()
    {
        if (!isset($GLOBALS['TSFE'])) {
            return null;
        }
        if (!$GLOBALS['TSFE']->id) {
            return null;
        }

        /** @var \TYPO3\CMS\Core\Site\SiteFinder $siteFinder */
        $siteFinder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Site\SiteFinder::class);
        return $siteFinder->getSiteByPageId($GLOBALS['TSFE']->id)->getIdentifier();
    }

    /**
     * Compares given main and sub context to the current one.
     *
     * @param string $mainContext
     * @param string $subContext
     * @return boolean
     */
    public static function runsInApplicationContext($mainContext = '', $subContext = '')
    {
        $currentMainContext = self::getMainApplicationContext();
        $currentSubContext = self::getApplicationSubContext();
        
        if (
            !empty($mainContext)
            && strtolower(trim($mainContext)) !== strtolower(trim($currentMainContext))) {
            return false;
        }
        
        if (
            !empty($subContext)
            && strtolower(trim($subContext)) !== strtolower(trim($currentSubContext))) {
            return false;
        }

        return true;
    }
    
    /**
     * Retrieves the current main application context.
     *
     * @return string
     */
    public static function getMainApplicationContext()
    {
        return self::$mainApplicationContext;
    }
    
    /**
     * Retrieves the current subcontext of the application.
     *
     * @return string
     */
    public static function getApplicationSubContext()
    {
        return self::$applicationSubContext;
    }
    
    /**
     * Retrieves GIT version for the root package
     *
     * @return string
     */
    protected static function retrieveApplicationVersion()
    {
        try {
            $versionDescription = exec(sprintf('cd "%s" && git describe --long', Environment::getProjectPath()));
            if (!preg_match('/^([a-z0-9\.\-]+)\-[0-9]+\-[a-z0-9]+$/', $versionDescription)) {
                return '(version not parsable)';
            }
            list($tag, $commitSinceTag, $commitHash) = explode('-', $versionDescription);

            $applicationVersion = $tag;
            if (intval($commitSinceTag) > 0) {
                $applicationVersion .= sprintf(' (+%s / %s)', $commitSinceTag, substr($commitHash, 1));
            }

        } catch (\Exception $exception) {
            $applicationVersion = '(version unretrievable)';
        }
        return $applicationVersion;
    }

    /**
     * Returns the application version string.
     *
     * @return string
     */
    public static function getApplicationVersion()
    {
        return self::$applicationVersion;
    }
}
