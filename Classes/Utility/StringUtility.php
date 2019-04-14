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

class StringUtility
{
    
    /**
     * Replaces all / any placeholders in a string.
     *
     * @var string $inputString
     * @var array $nestedValues
     * @var string $matchingPattern
     * @return string
     */
    public static function replacePlaceholders(
        string $inputString,
        array $nestedValues,
        $matchingPattern = '/\[\[typo3-site-operator:([^\]]+)\]\]/')
    {
        preg_match_all($matchingPattern, $inputString, $matches);
        foreach ($matches[1] as $matchNumber => $match) {
            $nestedValueSegments = explode('/', $match);
            $nestedValue = ArrayUtility::getNestedArrayValue($nestedValues, $nestedValueSegments) ?: '';
            $inputString = str_replace($matches[0][$matchNumber], $nestedValue, $inputString);
        }
        return $inputString;
    }
}
