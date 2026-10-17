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
use ReflectionMethod;

/**
 * Regression tests for GHSA-r7mf-4734-qgr3: the page-count command builds a PostScript
 * program by interpolating the asset's local file path into a string literal. That path
 * is derived from the asset's filename, which is only shell-escaped - never validated
 * against PostScript's own string-literal syntax - so a crafted filename could break out
 * of the literal and run arbitrary PostScript.
 */
class GhostscriptTest extends TestCase
{
    public function testLocalFileUsedForThePageCountCommandNeverCarriesTheAssetFilename(): void
    {
        $maliciousFilename = 'a) (.pipe.id - .tmp.rce_http) dup 0 37 put dup 5 37 put (w) file closefile (.pdf';
        $content = '%PDF-1.4 regression test content';

        $asset = new Asset\Document();
        $asset->setFilename($maliciousFilename);
        $asset->setData($content);

        $adapter = new Ghostscript();
        $adapter->load($asset);

        $localFile = $this->invokeCopyPdfToSafeLocalFile($adapter);

        try {
            self::assertStringNotContainsString(')', $localFile, 'the local file path must not contain any byte from the asset filename');
            self::assertStringNotContainsString('(', $localFile, 'the local file path must not contain any byte from the asset filename');
            self::assertMatchesRegularExpression(
                '#/temp-file-[0-9a-f]+-[0-9a-f]+\.pdf$#',
                $localFile,
                'the local file must be named by Pimcore, not derived from the asset'
            );

            self::assertFileExists($localFile);
            self::assertSame($content, file_get_contents($localFile), 'the pdf content must still be copied correctly');
        } finally {
            if (is_file($localFile)) {
                unlink($localFile);
            }
        }
    }

    public function testLegitimateFilenameStillProducesAReadableLocalCopy(): void
    {
        $content = '%PDF-1.4 regression test content';

        $asset = new Asset\Document();
        $asset->setFilename('invoice-2026.pdf');
        $asset->setData($content);

        $adapter = new Ghostscript();
        $adapter->load($asset);

        $localFile = $this->invokeCopyPdfToSafeLocalFile($adapter);

        try {
            self::assertFileExists($localFile);
            self::assertSame($content, file_get_contents($localFile));
        } finally {
            if (is_file($localFile)) {
                unlink($localFile);
            }
        }
    }

    private function invokeCopyPdfToSafeLocalFile(Ghostscript $adapter): string
    {
        $method = new ReflectionMethod(Ghostscript::class, 'copyPdfToSafeLocalFile');

        return $method->invoke($adapter);
    }
}
