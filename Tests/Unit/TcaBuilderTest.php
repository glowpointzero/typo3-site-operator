<?php
namespace Glowpointzero\SiteOperator\Tests\Unit;

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

use Glowpointzero\SiteOperator\Exception\TcaBuilderException;
use Glowpointzero\SiteOperator\TcaBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaBuilderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addingADateColumnResultsInCorrectInputConfig()
    {
        $tcaBuilder = TcaBuilder::create('my_ext', 'MyModel');
        $tcaBuilder->addDateColumn('mycolumn');
        $builtTca = $tcaBuilder->toArray();

        $this->assertEquals(
            'inputDateTime',
            $builtTca['columns']['mycolumn']['config']['renderType']
        );
        $this->assertEquals(
            'date',
            $builtTca['columns']['mycolumn']['config']['eval']
        );
    }

    /**
     * @test
     */
    public function addingADateTimeColumnResultsInCorrectInputConfig()
    {
        $tcaBuilder = TcaBuilder::create('my_ext', 'MyModel');
        $tcaBuilder->addDateTimeColumn('mycolumn');
        $builtTca = $tcaBuilder->toArray();

        $this->assertEquals(
            'input',
            $builtTca['columns']['mycolumn']['config']['type']
        );
        $this->assertEquals(
            'inputDateTime',
            $builtTca['columns']['mycolumn']['config']['renderType']
        );
        $this->assertEquals(
            'datetime',
            $builtTca['columns']['mycolumn']['config']['eval']
        );
    }

    /**
     * @test
     */
    public function fieldNamesAreAddedToThePalettesAndInterfaceConfigurations()
    {
        $tcaBuilder = TcaBuilder::create('my_ext', 'MyModel');
        $tcaBuilder
            ->addSingleLineInputColumn('my_column')
            ->addSingleLineInputColumn('my_column_in_a_palette')
            ->toPalette('my_first_palette');
        $builtTca = $tcaBuilder->toArray();

        $columnNames =  array_keys($builtTca['columns']);
        $this->assertContains('my_column', $columnNames, 'Column is not in main column array.');
        $this->assertContains('my_column_in_a_palette', $columnNames, 'Column that has been added to a palette is not in main column array.');

        $showRecordFieldList = GeneralUtility::trimExplode(',', $builtTca['interface']['showRecordFieldList']);
        $this->assertContains('my_column', $showRecordFieldList, 'interface -> showRecordFieldList is missing the column.');
        $this->assertContains('my_column_in_a_palette', $showRecordFieldList, 'interface -> showRecordFieldList is missing the column of a palette.');

        $type0ShowItem = GeneralUtility::trimExplode(',', $builtTca['types'][0]['showitem']);
        $this->assertContains('my_column', $type0ShowItem, 'interface -> showRecordFieldList is missing the column.');
        $this->assertContains('--palette--;;my_first_palette', $type0ShowItem, 'interface -> showRecordFieldList is missing the paletted column.');
    }

    /**
     * @test
     */
    public function tabsAreRepresentedByLabelledDividerElements()
    {
        $tcaBuilder = TcaBuilder::create('my_ext', 'MyModel');
        $tcaBuilder
            ->addSingleLineInputColumn('my_column')
            ->toPalette('my_palette')
            ->toTab('my_tab');
        $builtTca = $tcaBuilder->toArray();
        $this->assertEquals(
            '--div--;LLL:EXT:my_ext/Resources/Private/Language/Model/MyModel:propertyGroup.my_tab, --palette--;;my_palette',
            $builtTca['types'][0]['showitem']
        );
    }

    /**
     * @test
     */
    public function overruleMethodTriggersExceptionsIfNoPreviousElementExists()
    {
        $this->expectException(TcaBuilderException::class);
        $tcaBuilder = TcaBuilder::create('my_ext', 'MyModel');
        $tcaBuilder->andOverruleConfigurationWith(['foo' => 'bar']);
    }

    /**
     * @test
     */
    public function andMakeItRequiredMethodTriggersExceptionsIfPreviousElementIsAPalette()
    {
        $this->expectException(TcaBuilderException::class);
        $tcaBuilder = TcaBuilder::create('my_ext', 'MyModel');
        $tcaBuilder->addSingleLineInputColumn('testcolumn')
            ->toPalette('mypalette')
            ->andMakeItRequired();
    }

    /**
     * @test
     */
    public function overrulingConfiguurationTriggersExceptionsIfPreviousElementIsAPalette()
    {
        $this->expectException(TcaBuilderException::class);
        $tcaBuilder = TcaBuilder::create('my_ext', 'MyModel');
        $tcaBuilder->addSingleLineInputColumn('testcolumn')
            ->toPalette('mypalette')
            ->andOverruleConfigurationWith([]);
    }

    /**
     * @test
     */
    public function paletteConfigurationHasDefaultLabelPath()
    {
        $tcaBuilder = TcaBuilder::create('my_ext', 'MyModel');
        $tcaBuilder->addSingleLineInputColumn('testcolumn')
            ->toPalette('mypalette');
        $this->assertEquals(
            'LLL:EXT:my_ext/Resources/Private/Language/Model/MyModel:propertyGroup.mypalette',
            $tcaBuilder->toArray()['palettes']['mypalette']['label']
        );
    }
}
