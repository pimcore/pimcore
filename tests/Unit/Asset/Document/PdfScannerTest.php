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

namespace Pimcore\Tests\Unit\Asset\Document;

use Pimcore\Model\Asset\Document\PdfScanner;
use Pimcore\Tests\Support\Test\TestCase;

class PdfScannerTest extends TestCase
{
    public function testJsActionNameIsDetected(): void
    {
        $pdf = $this->wrapPdf(
            "1 0 obj\n<< /Type /Action /S /JavaScript /JS (app.alert(1);) >>\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testJavaScriptNameTreeIsDetected(): void
    {
        $pdf = $this->wrapPdf(
            "1 0 obj\n<< /Names << /JavaScript 2 0 R >> >>\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testHexEscapedJsNameIsDetected(): void
    {
        // /J#53 and /#4a#53 both decode to the name /JS (PDF 32000-1 §7.3.5)
        $this->assertTrue($this->scan($this->wrapPdf("<< /S /JavaScript /J#53 (x) >>\n")));
        $this->assertTrue($this->scan($this->wrapPdf("<< /S /JavaScript /#4a#53 (x) >>\n")));
    }

    public function testJsNameAtEndOfFileIsDetected(): void
    {
        // name token terminated by EOF instead of a delimiter
        $this->assertTrue($this->scan("%PDF-1.7\n<< /JS"));
    }

    public function testJsBytesInsideStreamPayloadAreIgnored(): void
    {
        // the reported false positive (#16955): /JS occurring as raw bytes
        // inside a (compressed) stream payload is not JavaScript
        $payload = "\x12\x88/JS\x99binary/JavaScript\x00garbage";
        $pdf = $this->wrapPdf(
            '2 0 obj<< /Length ' . strlen($payload) . " >>stream\n" . $payload . "\nendstream\nendobj\n"
        );

        $this->assertFalse($this->scan($pdf));
    }

    public function testJsNameAfterStreamPayloadIsDetected(): void
    {
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Length 10 >>\nstream\n0123456789\nendstream\nendobj\n" .
            "3 0 obj\n<< /S /JavaScript /JS (app.alert(1);) >>\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testNameWithJsPrefixIsNotDetected(): void
    {
        // /JSXTransform and /JavaScripted are different name tokens than /JS and /JavaScript
        $this->assertFalse($this->scan($this->wrapPdf("<< /Filter /JSXTransform >>\n")));
        $this->assertFalse($this->scan($this->wrapPdf("<< /Producer /JavaScripted >>\n")));
    }

    public function testStreamLikeNameDoesNotStartPayloadSkipping(): void
    {
        // "stream" as part of a name token is not the stream keyword — the
        // /JS after it must still be found
        $pdf = $this->wrapPdf("<< /Type /mystream\n/JS (app.alert(1);) >>\n");

        $this->assertTrue($this->scan($pdf));
    }

    public function testDetectionAcrossChunkBoundaries(): void
    {
        // tokens and keywords split across read-chunk boundaries must still be handled
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Length 30 >>\nstream\nbinary/JS...../JavaScript....\nendstream\nendobj\n" .
            "3 0 obj\n<< /S /JavaScript /JS (app.alert(1);) >>\nendobj\n"
        );

        foreach ([1, 2, 3, 7] as $chunkSize) {
            $this->assertTrue(
                $this->scan($pdf, $chunkSize),
                sprintf('real /JS missed with chunk size %d', $chunkSize)
            );
        }

        $cleanPdf = $this->wrapPdf(
            "2 0 obj\n<< /Length 30 >>\nstream\nbinary/JS...../JavaScript....\nendstream\nendobj\n"
        );

        foreach ([1, 2, 3, 7] as $chunkSize) {
            $this->assertFalse(
                $this->scan($cleanPdf, $chunkSize),
                sprintf('false positive with chunk size %d', $chunkSize)
            );
        }
    }

    public function testShortReadsDoNotAbortTheScan(): void
    {
        // fread() may return less than the requested chunk size (e.g. remote
        // Flysystem streams) — the scan must keep reading until EOF
        ShortReadStream::$content = $this->wrapPdf(
            str_repeat('x', 512) . "\n<< /S /JavaScript /JS (app.alert(1);) >>\n"
        );

        stream_wrapper_register('pimcore-shortread', ShortReadStream::class);

        try {
            $stream = fopen('pimcore-shortread://test', 'r');
            $this->assertTrue((new PdfScanner())->containsJavaScript($stream));
            fclose($stream);
        } finally {
            stream_wrapper_unregister('pimcore-shortread');
        }
    }

    public function testCleanPdfIsNotFlagged(): void
    {
        $pdf = $this->wrapPdf(
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n" .
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
        );

        $this->assertFalse($this->scan($pdf));
    }

    private function scan(string $content, ?int $chunkSize = null): bool
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $scanner = $chunkSize !== null ? new PdfScanner($chunkSize) : new PdfScanner();
        $result = $scanner->containsJavaScript($stream);

        fclose($stream);

        return $result;
    }

    private function wrapPdf(string $body): string
    {
        return "%PDF-1.7\n" . $body . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }
}

/**
 * Stream wrapper that returns at most one byte per read to simulate short reads.
 */
class ShortReadStream
{
    public static string $content = '';

    public mixed $context = null;

    private int $position = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->position = 0;

        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk = substr(self::$content, $this->position, 1);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$content);
    }

    public function stream_stat(): array
    {
        return [];
    }
}
