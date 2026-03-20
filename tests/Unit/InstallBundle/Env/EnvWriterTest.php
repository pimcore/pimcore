<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\InstallBundle\Env;

use Pimcore\Bundle\InstallBundle\Env\EnvWriter;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class EnvWriterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFile = sys_get_temp_dir() . '/pimcore_env_writer_test_' . uniqid() . '.env';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        parent::tearDown();
    }

    public function testWriteToNewFile(): void
    {
        $writer = new EnvWriter($this->tempFile);
        $warnings = $writer->write([
            'pimcore/pimcore' => [
                'DATABASE_URL' => 'mysql://user:pass@host:3306/db',
            ],
        ]);

        $this->assertSame([], $warnings);
        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('###> pimcore/pimcore ###', $content);
        $this->assertStringContainsString('DATABASE_URL="mysql://user:pass@host:3306/db"', $content);
        $this->assertStringContainsString('###< pimcore/pimcore ###', $content);
    }

    public function testWriteMultipleSections(): void
    {
        $writer = new EnvWriter($this->tempFile);
        $writer->write([
            'pimcore/pimcore' => [
                'DATABASE_URL' => 'mysql://user:pass@host:3306/db',
            ],
            'pimcore/studio-backend-bundle' => [
                'MERCURE_URL' => 'http://localhost/hub',
            ],
        ]);

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('###> pimcore/pimcore ###', $content);
        $this->assertStringContainsString('###> pimcore/studio-backend-bundle ###', $content);

        // Sections should be separated
        $pimcoreClose = strpos($content, '###< pimcore/pimcore ###');
        $studioOpen = strpos($content, '###> pimcore/studio-backend-bundle ###');
        $this->assertGreaterThan($pimcoreClose, $studioOpen);
    }

    public function testReplaceExistingSection(): void
    {
        file_put_contents($this->tempFile, <<<'ENV'
###> pimcore/pimcore ###
DATABASE_URL="mysql://old:old@host:3306/db"
###< pimcore/pimcore ###
ENV);

        $writer = new EnvWriter($this->tempFile);
        $writer->write([
            'pimcore/pimcore' => [
                'DATABASE_URL' => 'mysql://new:new@host:3306/db',
            ],
        ]);

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('mysql://new:new@host:3306/db', $content);
        $this->assertStringNotContainsString('mysql://old:old@host:3306/db', $content);
    }

    public function testPreserveContentOutsideSections(): void
    {
        file_put_contents($this->tempFile, <<<'ENV'
# My custom config
APP_ENV=dev
MY_CUSTOM_VAR=hello

###> pimcore/pimcore ###
DATABASE_URL="mysql://old:old@host:3306/db"
###< pimcore/pimcore ###
ENV);

        $writer = new EnvWriter($this->tempFile);
        $writer->write([
            'pimcore/pimcore' => [
                'DATABASE_URL' => 'mysql://new:new@host:3306/db',
            ],
        ]);

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('APP_ENV=dev', $content);
        $this->assertStringContainsString('MY_CUSTOM_VAR=hello', $content);
        $this->assertStringContainsString('mysql://new:new@host:3306/db', $content);
    }

    public function testWarnOnDuplicateEnvVarAcrossSections(): void
    {
        $writer = new EnvWriter($this->tempFile);
        $warnings = $writer->write([
            'section-a' => ['SHARED_VAR' => 'value-a'],
            'section-b' => ['SHARED_VAR' => 'value-b'],
        ]);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('SHARED_VAR', $warnings[0]);
        $this->assertStringContainsString('section-a', $warnings[0]);
        $this->assertStringContainsString('section-b', $warnings[0]);
    }

    public function testWarnOnMalformedMarkers(): void
    {
        file_put_contents($this->tempFile, <<<'ENV'
###> pimcore/pimcore ###
DATABASE_URL="old"
ENV);
        // Missing close marker

        $writer = new EnvWriter($this->tempFile);
        $warnings = $writer->write([
            'pimcore/pimcore' => ['DATABASE_URL' => 'new'],
        ]);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Malformed', $warnings[0]);
    }

    public function testEscapeSpecialCharactersInValues(): void
    {
        $writer = new EnvWriter($this->tempFile);
        $writer->write([
            'test' => [
                'PASSWORD' => 'pass"with\\special',
            ],
        ]);

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('PASSWORD="pass\\"with\\\\special"', $content);
    }

    public function testEscapeDollarSignInValues(): void
    {
        $writer = new EnvWriter($this->tempFile);
        $writer->write([
            'test' => [
                'PASSWORD' => 'pa$$word',
            ],
        ]);

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('PASSWORD="pa\\$\\$word"', $content);
    }

    public function testTrailingNewline(): void
    {
        $writer = new EnvWriter($this->tempFile);
        $writer->write([
            'test' => ['FOO' => 'bar'],
        ]);

        $content = file_get_contents($this->tempFile);
        $this->assertTrue(str_ends_with($content, "\n"));
    }

    public function testWriteCreatesFileFromScratch(): void
    {
        $this->assertFileDoesNotExist($this->tempFile);

        $writer = new EnvWriter($this->tempFile);
        $writer->write([
            'test' => ['FOO' => 'bar'],
        ]);

        $this->assertFileExists($this->tempFile);
        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('FOO="bar"', $content);
    }

    public function testWriteMultipleSectionsPreservesOrder(): void
    {
        $writer = new EnvWriter($this->tempFile);
        $writer->write([
            'section-a' => ['A_VAR' => 'a'],
            'section-b' => ['B_VAR' => 'b'],
            'section-c' => ['C_VAR' => 'c'],
        ]);

        $content = file_get_contents($this->tempFile);
        $posA = strpos($content, '###> section-a ###');
        $posB = strpos($content, '###> section-b ###');
        $posC = strpos($content, '###> section-c ###');

        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        $this->assertNotFalse($posC);
        $this->assertLessThan($posB, $posA);
        $this->assertLessThan($posC, $posB);
    }

    public function testOrphanedOpenMarkerIsCleanedUp(): void
    {
        file_put_contents($this->tempFile, <<<'ENV'
APP_ENV=dev

###> pimcore/pimcore ###
DATABASE_URL="old"
ENV);
        // Missing close marker — orphaned open marker

        $writer = new EnvWriter($this->tempFile);
        $warnings = $writer->write([
            'pimcore/pimcore' => ['DATABASE_URL' => 'new'],
        ]);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Malformed', $warnings[0]);

        $content = file_get_contents($this->tempFile);

        // The old orphaned marker and its content should be removed
        $this->assertStringNotContainsString('DATABASE_URL="old"', $content);

        // The new section should be present
        $this->assertStringContainsString('###> pimcore/pimcore ###', $content);
        $this->assertStringContainsString('DATABASE_URL="new"', $content);
        $this->assertStringContainsString('###< pimcore/pimcore ###', $content);

        // User content should be preserved
        $this->assertStringContainsString('APP_ENV=dev', $content);

        // There should be exactly one open marker (not two)
        $this->assertSame(1, substr_count($content, '###> pimcore/pimcore ###'));
    }

    public function testOrphanedCloseMarkerIsCleanedUp(): void
    {
        file_put_contents($this->tempFile, <<<'ENV'
APP_ENV=dev

DATABASE_URL="old"
###< pimcore/pimcore ###
ENV);
        // Missing open marker — orphaned close marker

        $writer = new EnvWriter($this->tempFile);
        $warnings = $writer->write([
            'pimcore/pimcore' => ['DATABASE_URL' => 'new'],
        ]);

        $this->assertCount(1, $warnings);

        $content = file_get_contents($this->tempFile);

        // There should be exactly one close marker (not two)
        $this->assertSame(1, substr_count($content, '###< pimcore/pimcore ###'));
        $this->assertStringContainsString('DATABASE_URL="new"', $content);
    }

    public function testWriteReplacesOnlySameSection(): void
    {
        // Write initial two sections
        $writer = new EnvWriter($this->tempFile);
        $writer->write([
            'section-a' => ['A_VAR' => 'original-a'],
            'section-b' => ['B_VAR' => 'original-b'],
        ]);

        // Replace only section-a
        $writer2 = new EnvWriter($this->tempFile);
        $writer2->write([
            'section-a' => ['A_VAR' => 'updated-a'],
        ]);

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('A_VAR="updated-a"', $content);
        $this->assertStringContainsString('B_VAR="original-b"', $content);
        $this->assertStringNotContainsString('original-a', $content);
    }
}
