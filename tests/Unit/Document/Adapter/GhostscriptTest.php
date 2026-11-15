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

use Pimcore\Document\Adapter\Ghostscript;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Console;
use ReflectionProperty;

/**
 * Regression tests for GHSA-r7mf-4734-qgr3: the page-count command builds a PostScript
 * program by interpolating the local file path into a string literal. On local asset
 * storage that path is the asset's own path on disk, which is derived from the asset's
 * filename and is only shell-escaped - never validated against PostScript's own
 * string-literal syntax - so a crafted filename could break out of the literal and run
 * arbitrary PostScript.
 */
class GhostscriptTest extends TestCase
{
    private const MALICIOUS_FILENAME = 'a) (.pipe.id - .tmp.rce_http) dup 0 37 put dup 5 37 put (w) file closefile (.pdf';

    private const PDF_CONTENT = '%PDF-1.4 regression test content';

    private const SAFE_COPY_PATTERN = '#/temp-file-[0-9a-f]+-[0-9a-f]+\.pdf$#';

    private array $executableCacheBackup = [];

    /**
     * @var string[]
     */
    private array $cleanupPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        // buildPageCountCommand() resolves the binaries through Console::getExecutable(). The command is
        // only built, never executed, so a fixed path satisfies the lookup without a Ghostscript install
        // and the (optional) "nice" prefix is disabled to keep the command deterministic.
        $cache = new ReflectionProperty(Console::class, 'executableCache');
        $backup = $cache->getValue();
        $this->executableCacheBackup = is_array($backup) ? $backup : [];
        $cache->setValue(null, array_merge($this->executableCacheBackup, ['gs' => '/usr/bin/gs', 'nice' => false]));
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Console::class, 'executableCache'))->setValue(null, $this->executableCacheBackup);

        foreach (array_reverse($this->cleanupPaths) as $path) {
            if (is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                rmdir($path);
            }
        }

        parent::tearDown();
    }

    public static function ghostscriptVersionProvider(): iterable
    {
        yield 'before 9.50: no --permit-file-read' => ['9.27', false];
        yield '9.50 and later: --permit-file-read added' => ['10.03.1', true];
    }

    /**
     * @dataProvider ghostscriptVersionProvider
     */
    public function testPageCountCommandNeverReferencesTheAssetPath(string $version, bool $expectPermitFileRead): void
    {
        $sourcePath = $this->createLocalPdf(self::MALICIOUS_FILENAME);

        $command = $this->buildPageCountCommandFor($sourcePath, self::MALICIOUS_FILENAME, $version);

        self::assertStringNotContainsString($sourcePath, $command, 'the asset\'s own path must never reach the command');
        self::assertStringNotContainsString(self::MALICIOUS_FILENAME, $command, 'no byte of the asset filename must reach the command');

        $postScriptFile = $this->extractPostScriptOperand($command);
        self::assertMatchesRegularExpression(self::SAFE_COPY_PATTERN, $postScriptFile, 'the PostScript operand must be a copy named by Pimcore');
        self::assertStringNotContainsString('(', $postScriptFile);
        self::assertStringNotContainsString(')', $postScriptFile);
        self::assertSame(self::PDF_CONTENT, file_get_contents($postScriptFile), 'the pdf content must still be copied correctly');

        if ($expectPermitFileRead) {
            self::assertSame(1, preg_match("#--permit-file-read='([^']*)'#", $command, $matches), '--permit-file-read not found in: ' . $command);
            self::assertSame($postScriptFile, $matches[1], '--permit-file-read must point at the same safe copy');
        } else {
            self::assertStringNotContainsString('--permit-file-read', $command);
        }
    }

    public function testLegitimateFilenameStillProducesAReadableCopy(): void
    {
        $filename = 'invoice-2026.pdf';
        $sourcePath = $this->createLocalPdf($filename);

        $command = $this->buildPageCountCommandFor($sourcePath, $filename, '10.03.1');

        $postScriptFile = $this->extractPostScriptOperand($command);
        self::assertMatchesRegularExpression(self::SAFE_COPY_PATTERN, $postScriptFile);
        self::assertFileExists($postScriptFile);
        self::assertSame(self::PDF_CONTENT, file_get_contents($postScriptFile));
    }

    /**
     * Creates a real local file with the given name, mirroring what the local asset storage hands to the
     * adapter: the stream's URI is the asset's own path on disk.
     */
    private function createLocalPdf(string $filename): string
    {
        $directory = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/ghostscript-test-' . uniqid();
        mkdir($directory, 0777, true);
        $this->cleanupPaths[] = $directory;

        $path = $directory . '/' . $filename;
        file_put_contents($path, self::PDF_CONTENT);
        $this->cleanupPaths[] = $path;

        return $path;
    }

    /**
     * Builds the page-count command for the given local file through the real adapter, with only the
     * Ghostscript version detection stubbed (it would otherwise execute the binary).
     */
    private function buildPageCountCommandFor(string $sourcePath, string $filename, string $version): string
    {
        $asset = new Asset\Document();
        $asset->setFilename($filename);
        $asset->setStream(fopen($sourcePath, 'rb'));

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

        return $adapter->load($asset)->buildCommand();
    }

    private function extractPostScriptOperand(string $command): string
    {
        self::assertSame(
            1,
            preg_match("#-c '\\((.*?)\\) \\(r\\) file runpdfbegin pdfpagecount = quit'#", $command, $matches),
            'PostScript operand not found in: ' . $command
        );

        $this->cleanupPaths[] = $matches[1];

        return $matches[1];
    }
}
