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

class ArrayUtility
{
    
    /**
     * Retrieves a value from a multidimensional
     * array, given the names / index of every node to
     * traverse.
     *
     * @return mixed
     */
    public static function getNestedArrayValue(array $inputArray, $pathSegments)
    {
        $detectedValue = $inputArray;
        foreach ($pathSegments as $pathSegment) {
            if (!array_key_exists($pathSegment, $detectedValue)) {
                return NULL;
            }
            $detectedValue = $detectedValue[$pathSegment];
        }
        return $detectedValue;
    }
}
