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

use Glowpointzero\SiteOperator\ProjectInstance;
use Glowpointzero\SiteOperator\Tests\Unit\UnitTestCase;
use Glowpointzero\SiteOperator\Utility\FileSystemUtility;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;

class FileSystemUtilityTest extends UnitTestCase
{
    public function setUp()
    {
        Environment::initialize(
            new ApplicationContext('Testing/Local'),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        ProjectInstance::initialize('my_package_key');
    }

    /**
     * @test
     */
    public function aFullyContextDependentFileTakesPrecedenceOverAnyLessSpecificFile()
    {
        $contextDependentFile = FileSystemUtility::getContextDependentFilePath(
            'EXT:site_operator/Tests/Unit/Fixtures/ContextDependentFiles/allFilesAvailable',
            'myfile',
            'txt'
        );
        $this->assertEquals(
            'EXT:site_operator/Tests/Unit/Fixtures/ContextDependentFiles/allFilesAvailable/myfile.testing.local.txt',
            $contextDependentFile
        );
    }

    /**
     * @test
     */
    public function mainContextOnlyFallbackIsUsedIfNoFileWithFullContextNamingIsAvailable()
    {
        $contextDependentFile = FileSystemUtility::getContextDependentFilePath(
            'EXT:site_operator/Tests/Unit/Fixtures/ContextDependentFiles/onlyMainContextAvailable',
            'myfile',
            'txt'
        );
        $this->assertEquals(
            'EXT:site_operator/Tests/Unit/Fixtures/ContextDependentFiles/onlyMainContextAvailable/myfile.testing.txt',
            $contextDependentFile
        );
    }

    /**
     * @test
     */
    public function contextLessFileWillBeUsedIfNoContextDependentFilesAreAvailable()
    {
        $contextDependentFile = FileSystemUtility::getContextDependentFilePath(
            'EXT:site_operator/Tests/Unit/Fixtures/ContextDependentFiles/noContextFileAvailable',
            'myfile',
            'txt'
        );
        $this->assertEquals(
            'EXT:site_operator/Tests/Unit/Fixtures/ContextDependentFiles/noContextFileAvailable/myfile.txt',
            $contextDependentFile
        );
    }
}
