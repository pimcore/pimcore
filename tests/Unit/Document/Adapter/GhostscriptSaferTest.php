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
 * Every Ghostscript invocation that interprets a PDF must run with -dSAFER explicitly.
 * A stand-in "gs" script records the arguments it is called with.
 */
class GhostscriptSaferTest extends TestCase
{
    private array $executableCacheBackup = [];

    private string $workDir;

    private string $argsFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workDir = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/gs-safer-test-' . uniqid();
        mkdir($this->workDir, 0777, true);
        $this->argsFile = $this->workDir . '/args.txt';

        $fakeGs = $this->workDir . '/gs';
        file_put_contents($fakeGs, "#!/bin/sh\nfor a in \"\$@\"; do printf '%s\\n' \"\$a\" >> " . escapeshellarg($this->argsFile) . "; done\nexit 0\n");
        chmod($fakeGs, 0755);

        $cache = new ReflectionProperty(Console::class, 'executableCache');
        $backup = $cache->getValue();
        $this->executableCacheBackup = is_array($backup) ? $backup : [];
        $cache->setValue(null, array_merge($this->executableCacheBackup, [
            'gs' => $fakeGs,
            'nice' => false,
            'pdftotext' => false,
        ]));
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Console::class, 'executableCache'))->setValue(null, $this->executableCacheBackup);

        foreach (glob($this->workDir . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->workDir);

        parent::tearDown();
    }

    /**
     * @return string[]
     */
    private function recordedArgs(): array
    {
        self::assertFileExists($this->argsFile, 'the Ghostscript binary was not invoked');

        return file($this->argsFile, FILE_IGNORE_NEW_LINES) ?: [];
    }

    private function createPdf(): string
    {
        $path = $this->workDir . '/input.pdf';
        file_put_contents($path, '%PDF-1.4 test');

        return $path;
    }

    private function adapter(bool $cropBox = false): Ghostscript
    {
        $asset = new Asset\Document();
        $asset->setFilename('input.pdf');
        $asset->setStream(fopen($this->createPdf(), 'rb'));

        $adapter = new class() extends Ghostscript {
            protected function getVersion(): string
            {
                return '9.27';
            }

            public function callConvert(?int $page, string $path): string
            {
                return $this->convertPdfToText($page, $path);
            }
        };

        $adapter->load($asset);
        if ($cropBox) {
            $adapter->setUseCropBox(true);
        }

        return $adapter;
    }

    public function testThumbnailRenderRunsWithSafer(): void
    {
        $target = $this->workDir . '/out.png';
        self::assertTrue($this->adapter()->saveImage($target, 2, 100));

        $args = $this->recordedArgs();
        self::assertContains('-dSAFER', $args);
        self::assertContains('-sDEVICE=pngalpha', $args);
        self::assertContains('-dFirstPage=2', $args);
        self::assertContains('-dLastPage=2', $args);
        self::assertContains($target, $args);

        $inputIndex = count($args) - 1;
        self::assertLessThan($inputIndex, array_search('-dSAFER', $args, true), '-dSAFER must precede the input file');
    }

    public function testThumbnailRenderWithCropBoxKeepsBothOptions(): void
    {
        self::assertTrue($this->adapter(true)->saveImage($this->workDir . '/out.png'));

        $args = $this->recordedArgs();
        self::assertContains('-dUseCropBox', $args);
        self::assertContains('-dSAFER', $args);
    }

    public function testTextExtractionRunsWithSafer(): void
    {
        try {
            $this->adapter()->callConvert(3, $this->createPdf());
        } catch (Exception $e) {
            // the stand-in binary writes no text file
        }

        $args = $this->recordedArgs();
        self::assertContains('-dSAFER', $args);
        self::assertContains('-sDEVICE=txtwrite', $args);
        self::assertContains('-dFirstPage=3', $args);
        self::assertContains($this->createPdf(), $args);
        self::assertLessThan(count($args) - 1, array_search('-dSAFER', $args, true));
    }

    public static function versionProvider(): iterable
    {
        yield 'before 9.50' => ['9.27'];
        yield '9.50 and later' => ['10.03.1'];
    }

    /**
     * @dataProvider versionProvider
     */
    public function testPageCountCommandRunsWithSafer(string $version): void
    {
        $asset = new Asset\Document();
        $asset->setFilename('input.pdf');
        $asset->setStream(fopen($this->createPdf(), 'rb'));

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
        };

        $command = $adapter->load($asset)->buildCommand();
        self::assertStringContainsString(' -dSAFER ', $command);
        self::assertLessThan(strpos($command, '-c '), strpos($command, '-dSAFER'));
    }
}
