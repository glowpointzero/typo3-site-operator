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

    /**
     * A simple comparison method allowing for the second
     * argument to be a pattern. Plus, this method is
     * case-insensitive by default.
     *
     * @param string $string
     * @param string $pattern
     * @param bool $ignoreCase
     * @return bool
     */
    public static function stringsMatch(string $string, string $pattern, bool $ignoreCase = true)
    {
        $useRegex = substr($pattern, 0, 1) === '/' && substr_count($pattern, '/') > 1;

        if ($useRegex && preg_match($pattern, $string)) {
            return true;
        }

        if ($ignoreCase) {
            $string = strtolower($string);
            $pattern = strtolower($pattern);
        }

        if (!$useRegex && $string === $pattern) {
            return true;
        }

        return false;
    }

    /**
     * @param string $originalString
     * @param int $maximumStringLength
     * @param string $suffix
     * @return string
     */
    public static function createExcerpt(string $originalString, int $maximumStringLength = 30, string $suffix = ' (...)')
    {
        if (strlen($originalString) <= $maximumStringLength) {
            return $originalString;
        }
        $excerpt = substr($originalString, 0, $maximumStringLength-strlen($suffix)) . $suffix;

        return $excerpt;
    }
}
