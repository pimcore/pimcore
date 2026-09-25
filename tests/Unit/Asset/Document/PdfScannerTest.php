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
    private const OBJECT_STREAM_WITH_JS = '5 0 << /S /JavaScript /JS (app.alert(1);) >>';

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

    public function testContentBetweenDeclaredLengthAndEndstreamIsScanned(): void
    {
        // a stream's data ends at its declared /Length; what follows up to
        // the endstream keyword is regular file content
        $trailing = "\n>> \n5 0 obj\n<< /S /JavaScript /JS (app.alert(1);) >>\nendobj\n";
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Length 10 >>\nstream\n0123456789" . $trailing . 'endstream' . "\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testContentAfterEndstreamIsScannedWhenDeclaredLengthIsTooLong(): void
    {
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Length 99999999 >>\nstream\nabc\nendstream\nendobj\n" .
            "5 0 obj\n<< /S /JavaScript /JS (app.alert(1);) >>\nendobj\n"
        );

        foreach ([null, 1, 2, 3, 7, 16] as $chunkSize) {
            $this->assertTrue($this->scan($pdf, $chunkSize), sprintf('missed with chunk size %s', $chunkSize ?? 'default'));
        }
    }

    public function testFlateCompressedObjectStreamWithJsIsDetected(): void
    {
        $compressed = gzcompress(self::OBJECT_STREAM_WITH_JS);
        $pdf = $this->objectStreamPdf('/Filter /FlateDecode /Length ' . strlen($compressed), $compressed);

        foreach ([null, 1, 2, 3, 7, 16] as $chunkSize) {
            $this->assertTrue($this->scan($pdf, $chunkSize), sprintf('missed with chunk size %s', $chunkSize ?? 'default'));
        }
    }

    public function testCompressedObjectStreamWithIndirectLengthIsInspected(): void
    {
        $compressed = gzcompress(self::OBJECT_STREAM_WITH_JS);
        $pdf = $this->objectStreamPdf('/Filter /FlateDecode /Length 3 0 R', $compressed);

        foreach ([null, 1, 7] as $chunkSize) {
            $this->assertTrue($this->scan($pdf, $chunkSize), sprintf('missed with chunk size %s', $chunkSize ?? 'default'));
        }
    }

    public function testCompressedObjectStreamWithNestedDictionaryIsInspected(): void
    {
        $compressed = gzcompress(self::OBJECT_STREAM_WITH_JS);
        $pdf = $this->objectStreamPdf(
            '/Filter /FlateDecode /Length ' . strlen($compressed) . ' /DecodeParms << /Columns 1 >>',
            $compressed
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testFilterChainsAroundFlateAreDecoded(): void
    {
        $compressed = gzcompress(self::OBJECT_STREAM_WITH_JS);

        $hex = bin2hex($compressed) . '>';
        $this->assertTrue($this->scan(
            $this->objectStreamPdf('/Filter [/ASCIIHexDecode /FlateDecode] /Length ' . strlen($hex), $hex)
        ));

        $ascii85 = $this->ascii85Encode($compressed) . '~>';
        $this->assertTrue($this->scan(
            $this->objectStreamPdf('/Filter [/ASCII85Decode /FlateDecode] /Length ' . strlen($ascii85), $ascii85)
        ));
    }

    public function testCompressedObjectStreamWithInvalidChecksumIsInspected(): void
    {
        // readers don't verify the trailing Adler-32 checksum of Flate data
        $compressed = substr(gzcompress(self::OBJECT_STREAM_WITH_JS), 0, -4) . "\0\0\0\0";
        $pdf = $this->objectStreamPdf('/Filter /FlateDecode /Length ' . strlen($compressed), $compressed);

        $this->assertTrue($this->scan($pdf));
    }

    public function testLargeCompressedObjectStreamIsInspectedBeyondFirstMegabytes(): void
    {
        $compressed = gzcompress('5 0 ' . str_repeat(' ', 9 * 1024 * 1024) . self::OBJECT_STREAM_WITH_JS);
        $pdf = $this->objectStreamPdf('/Filter /FlateDecode /Length ' . strlen($compressed), $compressed);

        $this->assertTrue($this->scan($pdf));
    }

    public function testObjectStreamTooLargeToInspectIsFlagged(): void
    {
        // an object stream decompressing beyond the inspection bound can't be
        // cleared; legitimate object streams are orders of magnitude smaller
        $compressed = gzcompress('5 0 ' . str_repeat("\0", 65 * 1024 * 1024) . '<< >>');
        $pdf = $this->objectStreamPdf('/Filter /FlateDecode /Length ' . strlen($compressed), $compressed);

        $this->assertTrue($this->scan($pdf));
    }

    public function testUnfulfillableDeclaredLengthPastTheBufferCapIsFlagged(): void
    {
        // the declared length exceeds both the actual file and the buffer
        // cap, so it can never be validated and the retained prefix never
        // recovers a literal endstream either — the discarded remainder
        // can't be ruled out, so this can't be certified safe
        $content = str_repeat('x', 17 * 1024 * 1024);
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Length " . (20 * 1024 * 1024) . " >>\nstream\n" . $content . "\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testUncompressedObjectStreamIsInspected(): void
    {
        $pdf = $this->objectStreamPdf('/Length ' . strlen(self::OBJECT_STREAM_WITH_JS), self::OBJECT_STREAM_WITH_JS);

        $this->assertTrue($this->scan($pdf));
    }

    public function testCleanFlateCompressedStreamIsNotFlagged(): void
    {
        $decompressed = str_repeat('clean content stream data ', 20);
        $compressed = gzcompress($decompressed);
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Filter /FlateDecode /Length " . strlen($compressed) . " >>\nstream\n" .
            $compressed . "\nendstream\nendobj\n"
        );

        $this->assertFalse($this->scan($pdf));
    }

    public function testCompressedDataThatIsNoObjectStreamIsNotFlagged(): void
    {
        // decompressed image or page content may contain /JS-like bytes by
        // chance; only object streams can hold objects and thus actions (see #16955)
        foreach (["\x80\x12/JS \xff pixel data /JavaScript\x00", 'BT /JS 12 Tf (text) Tj ET'] as $decompressed) {
            $compressed = gzcompress($decompressed);
            $pdf = $this->wrapPdf(
                "2 0 obj\n<< /Filter /FlateDecode /Length " . strlen($compressed) . " >>\nstream\n" .
                $compressed . "\nendstream\nendobj\n"
            );

            $this->assertFalse($this->scan($pdf));
        }
    }

    public function testIndirectLengthFallsBackToEndstreamSearch(): void
    {
        // raw bytes that merely look like a name token are not a detection (see #16955)
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Length 5 0 R >>\nstream\n/JS binary garbage\nendstream\nendobj\n"
        );

        $this->assertFalse($this->scan($pdf));
    }

    public function testUnsupportedFilterStreamIsNotInspected(): void
    {
        // e.g. image data behind DCTDecode — not text, not decompressed, and
        // a coincidental name-like byte sequence must not be a detection
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Filter /DCTDecode /Length 6 >>\nstream\n/JS \x00\xff\nendstream\nendobj\n"
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

    private function objectStreamPdf(string $dictionaryEntries, string $payload): string
    {
        return $this->wrapPdf(
            "2 0 obj\n<< /Type /ObjStm /N 1 /First 4 " . $dictionaryEntries . " >>\nstream\n" .
            $payload . "\nendstream\nendobj\n3 0 obj\n" . strlen($payload) . "\nendobj\n"
        );
    }

    private function ascii85Encode(string $data): string
    {
        $encoded = '';
        foreach (str_split($data, 4) as $group) {
            $padding = 4 - strlen($group);
            $value = unpack('N', str_pad($group, 4, "\0"))[1];
            $digits = '';
            for ($i = 0; $i < 5; $i++) {
                $digits = chr($value % 85 + 33) . $digits;
                $value = intdiv($value, 85);
            }
            $encoded .= substr($digits, 0, 5 - $padding);
        }

        return $encoded;
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
