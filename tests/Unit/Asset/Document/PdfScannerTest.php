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

    public function testStreamKeywordInsideACommentIsNotTreatedAsRealStream(): void
    {
        // a genuine stream keyword is always preceded by a dictionary
        // (PDF 32000-1 §7.3.8.1); "stream" occurring inside a comment isn't
        // one, and must not make the /JS right after it look like it's
        // sitting inside undecoded, unscanned payload
        $pdf = $this->wrapPdf(
            "5 0 obj\n<< /Foo % stream\n/S /JavaScript /JS (app.alert(1);) >>\n% endstream\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testUnterminatedCommentBeforeAStreamCandidateInvalidatesIt(): void
    {
        // the dictionary text a candidate is checked against is deliberately
        // truncated right before that candidate; if a comment within it
        // hasn't reached its own terminator by that cutoff, the comment may
        // really extend past the candidate too, making it part of the
        // comment rather than a real stream keyword — a valid dictionary
        // followed by such a comment must not be trusted as truly preceding it
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Length 100 >> % stream\n/JS /JavaScript /S (app.alert(1);) obj\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testTerminatedCommentBetweenDictionaryAndStreamIsStillRecognized(): void
    {
        // unlike the unterminated case above, a comment that reaches its
        // own end-of-line before the stream keyword doesn't put that
        // keyword's candidacy in doubt
        $compressed = gzcompress(self::OBJECT_STREAM_WITH_JS);
        $pdf = $this->wrapPdf(
            '2 0 obj' . "\n" . '<< /Type /ObjStm /N 1 /First 4 /Filter /FlateDecode /Length '
            . strlen($compressed) . ' >> % just a note' . "\n" . 'stream' . "\n"
            . $compressed . "\nendstream\nendobj\n3 0 obj\n" . strlen($compressed) . "\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testManyRejectedStreamCandidatesAreFlaggedRatherThanRescannedForever(): void
    {
        // each "stream"-shaped candidate lacking a preceding dictionary
        // re-parses the retained context to confirm that; a document packed
        // with many such candidates while that context stays large would
        // otherwise force that cost over and over, without bound
        $padding = str_repeat('x', 1024 * 1024);
        $fakeTokens = str_repeat("stream\n", 2000);

        $this->assertTrue($this->scan($this->wrapPdf($padding . $fakeTokens)));
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

    public function testCrlfSplitExactlyAcrossAChunkBoundaryDoesNotShiftThePayload(): void
    {
        // findStreamKeyword's lookahead can match on a lone trailing \r
        // merely because it can't see past the end of the buffer yet, even
        // though the real file has \r\n; if that \r were treated as a
        // complete one-byte EOL, the payload would start one byte early,
        // shifting a declared length so it ends one byte short — losing, for
        // example, the closing '>' of an ASCII85 terminator
        $ascii85 = $this->ascii85Encode(self::OBJECT_STREAM_WITH_JS) . '~>';
        $prefix = '2 0 obj' . "\n" . '<< /Type /ObjStm /N 1 /First 4 /Filter /ASCII85Decode /Length '
            . strlen($ascii85) . ' >>' . "\n" . 'stream';
        $wrapped = $this->wrapPdf(
            $prefix . "\r\n" . $ascii85 . "\nendstream\nendobj\n3 0 obj\n" . strlen($ascii85) . "\nendobj\n"
        );

        // the trailing \r of "stream\r\n" sits right at the header length
        // (everything before it, from wrapPdf's own preamble); splitting the
        // read exactly there is the case that can't yet tell \r from \r\n
        $crOffset = strpos($wrapped, "stream\r\n") + strlen('stream') + 1;

        foreach ([$crOffset, $crOffset + 1, null] as $chunkSize) {
            $this->assertTrue($this->scan($wrapped, $chunkSize), sprintf('missed with chunk size %s', $chunkSize ?? 'default'));
        }
    }

    public function testFlateThenAscii85FilterChainIsDecoded(): void
    {
        // /Filter [/FlateDecode /ASCII85Decode] decodes Flate first, then
        // ASCII85 — the opposite order from testFilterChainsAroundFlateAreDecoded;
        // inflating must not be the final decode attempt
        $ascii85 = $this->ascii85Encode(self::OBJECT_STREAM_WITH_JS) . '~>';
        $compressed = gzcompress($ascii85);
        $pdf = $this->objectStreamPdf(
            '/Filter [/FlateDecode /ASCII85Decode] /Length ' . strlen($compressed),
            $compressed
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testUnresolvedLengthTruncatedWithoutRecoverableEndstreamIsFlagged(): void
    {
        // same as the declared-but-unfulfillable case: once a payload
        // recovered without any validated length has been truncated at the
        // buffer cap, and no endstream is ever found, the discarded
        // remainder can't be ruled out and this can't be certified safe
        $content = str_repeat('x', 17 * 1024 * 1024);
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Length 5 0 R >>\nstream\n" . $content . "\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testTruncatedPayloadWithUnresolvedTypeAndBlankRetainedPrefixIsFlagged(): void
    {
        // a truncated payload's retained prefix is genuinely incomplete —
        // the real header may sit beyond it, entirely discarded. If that
        // prefix happens to be nothing but whitespace, treating it as if
        // it were the complete content would wrongly conclude "not an
        // object stream" instead of "inconclusive", certifying as safe an
        // object stream whose real JS sits just past what was kept
        $realJs = self::OBJECT_STREAM_WITH_JS;
        $content = '% ' . str_repeat(' ', 17 * 1024 * 1024) . $realJs;
        $pdf = $this->wrapPdf(
            '2 0 obj' . "\n" . '<< /Type 9 0 R /Length ' . strlen($content) . ' >>' . "\n"
            . 'stream' . "\n" . $content . "\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
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

    public function testIndirectLengthObjectStreamWithEmbeddedEndstreamBytesIsFlagged(): void
    {
        // an indirect length is recovered by searching for the literal
        // endstream keyword, a boundary a raw/stored deflate block can always
        // spoof by embedding that exact byte sequence earlier in the
        // payload; an object stream recovered this way can't be proven
        // complete and so can't be certified safe, even without finding /JS
        // in the (possibly truncated-at-the-fake-marker) recovered payload
        $decompressed = '5 0 ' . str_repeat(' ', 40) . 'endstream' . str_repeat(' ', 40) . '<< >> endobj';
        $compressed = gzcompress($decompressed, 0);
        self::assertTrue(str_contains($compressed, 'endstream'), 'test setup: compressed bytes must contain "endstream" literally');

        $pdf = $this->objectStreamPdf('/Filter /FlateDecode /Length 3 0 R', $compressed);

        $this->assertTrue($this->scan($pdf));
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

    public function testFilterChainDeeperThanTheDecodeBudgetIsFlagged(): void
    {
        // a legal filter chain can legitimately stack more layers than any
        // fixed recursion budget allows for; exhausting it while the data
        // still looks decodable can't be certified safe, since a reader
        // would still apply the remaining filter
        $data = self::OBJECT_STREAM_WITH_JS;
        for ($i = 0; $i < 5; $i++) {
            $data = bin2hex($data) . '>';
        }

        $pdf = $this->objectStreamPdf('/Filter [/ASCIIHexDecode /ASCIIHexDecode /ASCIIHexDecode /ASCIIHexDecode /ASCIIHexDecode] /Length ' . strlen($data), $data);

        $this->assertTrue($this->scan($pdf));
    }

    public function testTypedNonObjectStreamExceedingTheDecodeBudgetIsNotFlagged(): void
    {
        // a stream explicitly typed as something other than an object
        // stream can never hold JavaScript, and exhausting the decode
        // budget on it must not override that — only an unknown or
        // ObjStm-typed stream fails closed when exhaustion is reached
        $data = 'clean image bytes, nothing JS-like at all here';
        for ($i = 0; $i < 5; $i++) {
            $data = bin2hex($data) . '>';
        }

        $pdf = $this->wrapPdf(
            '2 0 obj' . "\n"
            . '<< /Type /XObject /Subtype /Image /Filter [/ASCIIHexDecode /ASCIIHexDecode /ASCIIHexDecode /ASCIIHexDecode /ASCIIHexDecode] /Length '
            . strlen($data) . ' >>' . "\n" . 'stream' . "\n" . $data . "\nendstream\nendobj\n"
        );

        $this->assertFalse($this->scan($pdf));
    }

    public function testFilterChainWithinTheDecodeBudgetIsNotFlaggedWhenClean(): void
    {
        // a chain that fully decodes within budget to something that isn't
        // an object stream must not be flagged just for looking encoded
        $data = bin2hex('just some ordinary clean text content') . '>';
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Filter /ASCIIHexDecode /Length " . strlen($data) . " >>\nstream\n" . $data . "\nendstream\nendobj\n"
        );

        $this->assertFalse($this->scan($pdf));
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

    public function testManyStreamsEachUnderTheLimitAreFlaggedOnceTheDocumentBudgetIsSpent(): void
    {
        // several clean object streams that each individually stay under
        // the per-stream cap can still force a huge amount of cumulative
        // decompression across one document; once that document-wide
        // budget is spent, the rest of it can't be certified safe either
        $body = '';
        for ($n = 0; $n < 6; $n++) {
            $compressed = gzcompress('5 0 ' . str_repeat(' ', 50 * 1024 * 1024) . '42');
            $body .= (2 + $n) . " 0 obj\n<< /Type /ObjStm /N 1 /First 4 /Filter /FlateDecode /Length "
                . strlen($compressed) . " >>\nstream\n" . $compressed . "\nendstream\nendobj\n";
        }

        $this->assertTrue($this->scan($this->wrapPdf($body)));
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

    public function testCleanCompressedObjectStreamIsNotFlagged(): void
    {
        // once decoded content is confirmed as genuine object-stream syntax
        // and thoroughly scanned for /JS, that's a final answer — trying
        // (and failing) further decodings on top of it must not override a
        // genuinely clean result with a fail-closed one
        $decompressed = '488 0 489 19 490 115 491 209 [/ICCBased 4 0 R] endobj 492 0 obj << >> endobj';
        $compressed = gzcompress($decompressed);
        $pdf = $this->objectStreamPdf('/Filter /FlateDecode /Length ' . strlen($compressed), $compressed);

        $this->assertFalse($this->scan($pdf));
    }

    public function testCleanScalarOnlyObjectStreamIsNotFlagged(): void
    {
        // an object stream may legitimately hold only scalar objects, with
        // no dictionary anywhere in its decoded content; the declared
        // /Filter's step count having been reached is what makes this
        // trustworthy, not the shape alone (which a still-encoded payload
        // could otherwise coincidentally match too)
        $decompressed = '5 0 42';
        $compressed = gzcompress($decompressed);
        $pdf = $this->objectStreamPdf('/Filter /FlateDecode /Length ' . strlen($compressed), $compressed);

        $this->assertFalse($this->scan($pdf));
    }

    public function testCleanScalarOnlyUncompressedObjectStreamIsNotFlagged(): void
    {
        // an absent /Filter declares zero decode steps — a known quantity,
        // not an unknown one the way an indirect reference or an
        // unparseable filter array is — so this is trustworthy immediately,
        // without ever needing a '<<'/'>>' to appear
        $decompressed = '5 0 42';
        $pdf = $this->objectStreamPdf('/Length ' . strlen($decompressed), $decompressed);

        $this->assertFalse($this->scan($pdf));
    }

    public function testPlusPrefixedObjectStreamHeaderIsRecognizedWithoutType(): void
    {
        // PDF integers may carry an optional leading '+' (PDF 32000-1
        // §7.3.3), including the object-number/generation pair that opens
        // an object stream's decoded content; the structural fallback used
        // when /Type is missing or indirect must recognize this shape too
        $decompressed = '+5 +0 << /S /JavaScript /JS (app.alert(1);) >>';
        $compressed = gzcompress($decompressed);
        $pdf = $this->wrapPdf(
            "2 0 obj\n<< /Filter /FlateDecode /Length " . strlen($compressed) . " >>\nstream\n"
            . $compressed . "\nendstream\nendobj\n"
        );

        $this->assertTrue($this->scan($pdf));
    }

    public function testObjectStreamWithUnsupportedEncodingIsFlagged(): void
    {
        // a /Type-confirmed object stream using an encoding this class
        // can't decode at all (e.g. LZW, or Flate with a predictor) never
        // resolves to content recognizable as decoded object-stream syntax
        // and can't be certified safe just because none of the sniffed
        // decodings applied to it
        $opaqueBytes = "\x80\x0b\x60\x50\x22\x0c\x0c\x85\x01";
        $pdf = $this->objectStreamPdf('/Filter /LZWDecode /Length ' . strlen($opaqueBytes), $opaqueBytes);

        $this->assertTrue($this->scan($pdf));
    }

    public function testAsciiHexEncodedJsIsDecodedDespiteLookingLikeAnObjectStreamHeader(): void
    {
        // ASCIIHex text can start with what, once whitespace is ignored,
        // reads as a plausible object-number pair (e.g. "35 20 30 20" —
        // still just hex digits for "5 0 <"); the shape alone must not be
        // mistaken for already-decoded content, or the real hex decode is
        // skipped entirely and the JS underneath it is never found
        $hexPairs = str_split(bin2hex(self::OBJECT_STREAM_WITH_JS), 2);
        $hex = implode(' ', $hexPairs) . '>';

        $pdf = $this->objectStreamPdf('/Filter /ASCIIHexDecode /Length ' . strlen($hex), $hex);

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
