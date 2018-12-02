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

class StringUtility
{
    
    /**
     * Replaces all / any placeholders in a string.
     *
     * @var string $inputString
     * @var array $nestedValues
     * @return string
     */
    public static function replacePlaceholders($inputString, $nestedValues)
    {
        preg_match_all('/\[\[typo3-site-operator:([^\]]+)\]\]/', $inputString, $matches);
        foreach ($matches[1] as $matchNumber => $match) {
            $nestedValueSegments = explode('/', $match);
            $nestedValue = ArrayUtility::getNestedArrayValue($nestedValues, $nestedValueSegments) ?: '';
            $inputString = str_replace($matches[0][$matchNumber], $nestedValue, $inputString);
        }
        return $inputString;
    }
}
