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

namespace Pimcore\Tests\Unit\Document\Adapter;

use Exception;
use Pimcore\Document\Adapter\Ghostscript;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Console;
use ReflectionProperty;

/**
 * Ghostscript is always run in SAFER mode, independent of the installed version.
 * A recording stand-in for the gs binary captures the arguments the adapter passes.
 */
class GhostscriptSaferModeTest extends TestCase
{
    private array $executableCacheBackup = [];

    /**
     * @var string[]
     */
    private array $cleanupPaths = [];

    private string $directory;

    private string $recordFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/gs-safer-test-' . uniqid();
        mkdir($this->directory, 0777, true);
        $this->recordFile = $this->directory . '/args.txt';

        $fakeGs = $this->directory . '/gs';
        file_put_contents($fakeGs, "#!/bin/sh\nprintf '%s\\n' \"\$@\" > " . $this->recordFile . "\nexit 0\n");
        chmod($fakeGs, 0755);

        $cache = new ReflectionProperty(Console::class, 'executableCache');
        $backup = $cache->getValue();
        $this->executableCacheBackup = is_array($backup) ? $backup : [];
        $cache->setValue(null, array_merge(
            $this->executableCacheBackup,
            ['gs' => $fakeGs, 'nice' => false, 'pdftotext' => false]
        ));
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Console::class, 'executableCache'))->setValue(null, $this->executableCacheBackup);

        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
        foreach ($this->cleanupPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    private function recordedArgs(): array
    {
        self::assertFileExists($this->recordFile, 'the gs stand-in was not invoked');

        return array_filter(explode("\n", (string) file_get_contents($this->recordFile)), 'strlen');
    }

    private function createAdapter(string $version = '10.03.1'): Ghostscript
    {
        $pdf = $this->directory . '/input.pdf';
        file_put_contents($pdf, '%PDF-1.4 test');

        $asset = new Asset\Document();
        $asset->setFilename('input.pdf');
        $asset->setStream(fopen($pdf, 'rb'));

        $adapter = new class($version) extends Ghostscript {
            public function __construct(private readonly string $stubbedVersion)
            {
            }

            protected function getVersion(): string
            {
                return $this->stubbedVersion;
            }

            public function buildCommand(): string
            {
                return $this->buildPageCountCommand();
            }

            public function text(string $path): string
            {
                return $this->convertPdfToText(null, $path);
            }
        };

        return $adapter->load($asset);
    }

    public static function cropBoxProvider(): iterable
    {
        yield 'media box' => [false];
        yield 'crop box' => [true];
    }

    /**
     * @dataProvider cropBoxProvider
     */
    public function testSaveImageRunsGhostscriptInSaferMode(bool $cropBox): void
    {
        $adapter = $this->createAdapter();
        $adapter->setUseCropBox($cropBox);
        $target = $this->directory . '/out.png';

        $adapter->saveImage($target, 1, 100);

        $args = $this->recordedArgs();
        self::assertContains('-dSAFER', $args);
        self::assertContains('-sDEVICE=pngalpha', $args);
        self::assertContains($target, $args);
    }

    public static function versionProvider(): iterable
    {
        yield 'before 9.50' => ['9.27'];
        yield '9.50 and later' => ['10.03.1'];
    }

    /**
     * @dataProvider versionProvider
     */
    public function testPageCountCommandUsesSaferMode(string $version): void
    {
        $command = $this->createAdapter($version)->buildCommand();
        $this->cleanupPaths[] = $this->extractCopy($command);

        self::assertMatchesRegularExpression('#\s-dSAFER(\s|$)#', $command);
        if (version_compare($version, '9.50', '>=')) {
            self::assertStringContainsString('--permit-file-read=', $command);
        }
    }

    public function testTextExtractionFallbackUsesSaferMode(): void
    {
        $adapter = $this->createAdapter();
        try {
            $adapter->text($this->directory . '/input.pdf');
        } catch (Exception $e) {
            // the stand-in writes no text file; only the arguments matter
        }

        $args = $this->recordedArgs();
        self::assertContains('-dSAFER', $args);
        self::assertContains('-sDEVICE=txtwrite', $args);
    }

    private function extractCopy(string $command): string
    {
        preg_match("#-c '\\((.*?)\\) \\(r\\)#", $command, $m);

        return $m[1] ?? '';
    }
}
