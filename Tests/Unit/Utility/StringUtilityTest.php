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
use Glowpointzero\SiteOperator\Utility\StringUtility;

class StringUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function defaultPatternRespectsNestedValues()
    {
        $result = StringUtility::replacePlaceholders(
            'abc [[typo3-site-operator:foo/bar]] def',
            ['foo' => ['bar' => 'baz']]
        );

        $this->assertSame('abc baz def', $result);
    }

    /**
     * Tests that multiple values, even empty (invalid) ones
     * will be replaced.
     *
     * @test
     */
    public function allOccurrencesAreReplaced()
    {
        $result = StringUtility::replacePlaceholders(
            'abc [[typo3-site-operator:foo/bar]] [[typo3-site-operator:mykey]] [[typo3-site-operator:invalidkey]]  def',
            [
                'foo' => ['bar' => 'baz'],
                'mykey' => 'myvalue'
            ]
        );

        $this->assertSame('abc baz myvalue   def', $result);
    }

    /**
     * @test
     */
    public function defaultPatternIsOverrideable()
    {
        $result = StringUtility::replacePlaceholders(
            'abc such-a-custom-pattern(mykey) def',
            ['mykey' => 'myvalue'],
            '/such-a-custom-pattern\((.*)\)/'
        );
        $this->assertSame('abc myvalue def', $result);
    }
}
