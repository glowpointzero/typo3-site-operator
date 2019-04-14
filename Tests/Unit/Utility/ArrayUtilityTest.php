<?php
namespace Glowpointzero\SiteOperator\Tests\Unit\Utility;

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

use Glowpointzero\SiteOperator\Tests\Unit\UnitTestCase;
use Glowpointzero\SiteOperator\Utility\ArrayUtility;

class ArrayUtilityTest extends UnitTestCase
{
    protected $testArray = [
        'foo' => [
            'bar' => 1,
            'baz' => 'testvalue',
            0 => 'zero',
            1 => 'one'
        ],
        'bar' => [
            'baz' => false,
            'emptyTestArray' => [],
            'subarray' => ['apple', 'orange'],
            '0' => 'also zero',
            '1' => 'also one',
            1 => 'one, but as integer'
        ]
    ];

    /**
     * @test
     */
    public function attemptsToRetrieveNonExistantArrayElementsReturnNull()
    {
        $result1 = ArrayUtility::getNestedArrayValue($this->testArray, ['nonexistant']);
        $this->assertNull($result1);

        $result2 = ArrayUtility::getNestedArrayValue($this->testArray, ['foo', 'nonexistant']);
        $this->assertNull($result2);
    }

    /**
     * @test
     */
    public function nestedValuesMayBeRetrieved()
    {
        $this->assertSame(
            ['apple', 'orange'],
            ArrayUtility::getNestedArrayValue($this->testArray, ['bar', 'subarray'])
        );
    }

    /**
     * @test
     */
    public function numericSegmentStringsRetrieveIntegerIndexValuesAsWell()
    {
        $this->assertSame(
            'zero',
            ArrayUtility::getNestedArrayValue($this->testArray, ['foo', '0'])
        );
    }

    /**
     * @test
     */
    public function integerSegmentValuesRetrieveStringIndexValuesAsWell()
    {
        $this->assertSame(
            'also zero',
            ArrayUtility::getNestedArrayValue($this->testArray, ['bar', 0])
        );
    }
}
