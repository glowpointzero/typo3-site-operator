<?php
namespace Glowpointzero\SiteOperator\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Glowpointzero\SiteOperator\ProjectInstance;

class FileSystemUtility
{
    /**
     * Returns the path to a given file that may have multiple
     * versions for different application contexts.
     *
     * EXT:my_extension/Configuration/TypoScript/constants.typoscript
     * may also exist as constants.development.typoscript, or - even more specific -
     * as constants.development.staging.typoscript. This method will find
     * the correct version and return its path, relying on a initialized
     * 'ProjectInstance'.
     *
     * Example:
     * - We're in the Application context 'Production'
     * - getContextDependentFilePath('fileadmin', 'test', 'txt')
     * Returns: 'fileadmin/test.production.txt', if it exists,
     * 'test.txt' otherwise.
     *
     * @param string $directory
     * @param string $fileName
     * @param string $fileSuffix
     * @return string|bool
     */
    public static function getContextDependentFilePath($directory, $fileName, $fileSuffix)
    {
        $directory = rtrim($directory, '/');
        $fileName = rtrim($fileName, '.');
        $fileSuffix = ltrim($fileSuffix, '.');

        // File names from most specific to least specific!
        $fileNameCandidates = [
            $fileName . '.' 
            . strtolower(ProjectInstance::getMainApplicationContext()) 
            . '.' . strtolower(ProjectInstance::getApplicationSubContext())
            . '.' . $fileSuffix,
            
            $fileName . '.' . strtolower(ProjectInstance::getMainApplicationContext()) . '.' . $fileSuffix,
            
            $fileName . '.' . $fileSuffix
        ];
        
        foreach ($fileNameCandidates as $fileNameCandidate) {
            $filePathCandidate = $directory . '/' . $fileNameCandidate;
            $absoluteFilePath = GeneralUtility::getFileAbsFileName($filePathCandidate);
            if (file_exists($absoluteFilePath)) {
                return $filePathCandidate;
            }
        }
        
        return false;        
    }
}
