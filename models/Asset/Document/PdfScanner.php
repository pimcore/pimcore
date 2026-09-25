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

namespace Pimcore\Model\Asset\Document;

/**
 * Scans a PDF for JavaScript by matching /JS and /JavaScript as proper PDF name
 * tokens, instead of raw byte matching which flags binary (compressed) stream
 * data by chance (see #16955).
 *
 * A stream's own declared /Length (when a direct integer literal, not an
 * indirect reference) is used to find the payload's true end, the same way a
 * conforming reader does — instead of searching for the next literal
 * `endstream` keyword. This closes off smuggling a real name token into the
 * gap between the declared length and that keyword. A /FlateDecode-only
 * payload of bounded size is additionally inflated and scanned, since that is
 * exactly what real readers do with it (e.g. an object stream produced by
 * `qpdf --object-streams=generate`).
 *
 * This is a heuristic pre-check for the admin preview, not a sanitizer: a
 * stream whose length can't be determined statically, that uses any other
 * filter (images, LZW, filter chains, ...), or that exceeds the size bounds
 * below is still left unscanned, exactly as before.
 *
 * @internal
 */
final class PdfScanner
{
    private const STREAM_KEYWORD = 'stream';

    private const ENDSTREAM_KEYWORD = 'endstream';

    /**
     * Longest byte sequence that may straddle a chunk boundary: a fully
     * hex-escaped /JavaScript name (1 + 10 * 3 bytes) plus a terminating
     * delimiter, or the stream keyword with its EOL marker.
     */
    private const BOUNDARY_OVERLAP = 64;

    /**
     * Bounds how much of a single /FlateDecode stream payload is buffered for
     * inflation. Streams beyond this size stop being collected and are left
     * unscanned, same as any other filter this class doesn't inspect.
     */
    private const MAX_STREAM_BUFFER_BYTES = 4 * 1024 * 1024;

    /**
     * Bounds zlib_decode() output to defend against a decompression bomb
     * hidden in a small /FlateDecode stream.
     */
    private const MAX_INFLATED_BYTES = 8 * 1024 * 1024;

    public function __construct(private readonly int $chunkSize = 65536)
    {
    }

    /**
     * @param resource $stream seekable or non-seekable readable stream positioned at the start of the PDF
     */
    public function containsJavaScript($stream): bool
    {
        $buffer = '';
        $streamState = null;

        do {
            $chunk = feof($stream) ? '' : fread($stream, $this->chunkSize);
            if (is_string($chunk)) {
                $buffer .= $chunk;
            }
            $atEof = $chunk === false || $chunk === '' || feof($stream);

            if ($this->scanBuffer($buffer, $streamState, $atEof)) {
                return true;
            }
        } while (!$atEof);

        return false;
    }

    /**
     * Consumes the buffer, retaining an unconsumed tail so tokens and keywords
     * split across chunk boundaries are seen once completed by the next read.
     *
     * @param array{remaining: ?int, isFlate: bool, collected: string, truncated: bool}|null $streamState
     */
    private function scanBuffer(string &$buffer, ?array &$streamState, bool $atEof): bool
    {
        $position = 0;
        $length = strlen($buffer);

        while (true) {
            if ($streamState !== null) {
                if ($streamState['remaining'] === null) {
                    // length isn't known statically — fall back to skipping
                    // opaquely until the literal endstream keyword, exactly
                    // as this class always did before it inspected streams
                    $endstream = strpos($buffer, self::ENDSTREAM_KEYWORD, $position);
                    if ($endstream === false) {
                        $keep = $atEof ? 0 : strlen(self::ENDSTREAM_KEYWORD) - 1;
                        $buffer = $keep > 0 ? substr($buffer, max($position, $length - $keep)) : '';

                        return false;
                    }

                    $position = $endstream + strlen(self::ENDSTREAM_KEYWORD);
                    $streamState = null;

                    continue;
                }

                $consume = min($length - $position, $streamState['remaining']);
                if ($streamState['isFlate']) {
                    $this->collectStreamBytes($streamState, substr($buffer, $position, $consume));
                }
                $position += $consume;
                $streamState['remaining'] -= $consume;

                if ($streamState['remaining'] > 0) {
                    // payload continues beyond this chunk — everything
                    // available has been consumed already
                    $buffer = '';

                    return false;
                }

                if ($streamState['isFlate'] && $this->streamPayloadContainsJsName($streamState)) {
                    return true;
                }

                $streamState = null;

                continue;
            }

            $streamKeywordStart = $this->findStreamKeyword($buffer, $position);
            $regionEnd = $streamKeywordStart ?? $length;
            $regionIsFinal = $streamKeywordStart === null && $atEof;

            $region = substr($buffer, $position, $regionEnd - $position);
            if ($this->regionContainsJsName($region, $regionIsFinal)) {
                return true;
            }

            if ($streamKeywordStart === null) {
                // keep a tail: it may hold the start of a stream keyword or an
                // incomplete name token continued by the next read
                $keep = $atEof ? 0 : self::BOUNDARY_OVERLAP;
                $buffer = $keep > 0 ? substr($buffer, max($position, $length - $keep)) : '';

                return false;
            }

            // the keyword's EOL marker (guaranteed present by the lookahead
            // in findStreamKeyword) precedes the payload and isn't part of
            // the declared length
            $position = $streamKeywordStart + strlen(self::STREAM_KEYWORD);
            $position += ($buffer[$position] ?? '') === "\r" && ($buffer[$position + 1] ?? '') === "\n" ? 2 : 1;

            $streamState = [
                'remaining' => $this->detectStreamLength($region),
                'isFlate' => $this->detectStreamIsFlate($region),
                'collected' => '',
                'truncated' => false,
            ];
        }
    }

    /**
     * Reads the /Length declared in the stream's own dictionary (the last
     * `<<...` in the text preceding the `stream` keyword). Returns null when
     * it is missing or an indirect reference (e.g. `/Length 5 0 R`), which
     * this heuristic scanner can't resolve — the caller then falls back to
     * treating the stream exactly as before this class used /Length at all.
     */
    private function detectStreamLength(string $precedingText): ?int
    {
        $dictionary = $this->lastDictionary($precedingText);

        if (!preg_match('/\/Length\s+(\d+)(\s+\d+\s+R\b)?/', $dictionary, $match)) {
            return null;
        }

        if (isset($match[2]) && $match[2] !== '') {
            return null;
        }

        return (int) $match[1];
    }

    /**
     * True only for a stream declaring a single /FlateDecode filter — the
     * common case (including object streams produced by
     * `qpdf --object-streams=generate`) safe to inflate and scan. Any other
     * filter (images, LZW, filter chains, ...) is left unscanned.
     */
    private function detectStreamIsFlate(string $precedingText): bool
    {
        $dictionary = $this->lastDictionary($precedingText);

        if (!preg_match('/\/Filter\s*(\/[A-Za-z0-9]+|\[[^\]]*\])/', $dictionary, $match)) {
            return false;
        }

        $value = $match[1];
        if ($value[0] === '[') {
            if (!preg_match('/^\[\s*(\/[A-Za-z0-9]+)\s*\]$/', $value, $single)) {
                return false;
            }

            $value = $single[1];
        }

        return $value === '/FlateDecode';
    }

    private function lastDictionary(string $precedingText): string
    {
        $dictStart = strrpos($precedingText, '<<');

        return $dictStart === false ? $precedingText : substr($precedingText, $dictStart);
    }

    /**
     * @param array{remaining: ?int, isFlate: bool, collected: string, truncated: bool} $streamState
     */
    private function collectStreamBytes(array &$streamState, string $bytes): void
    {
        if ($streamState['truncated']) {
            return;
        }

        if (strlen($streamState['collected']) + strlen($bytes) > self::MAX_STREAM_BUFFER_BYTES) {
            // too large to buffer safely — fall back to leaving it unscanned
            $streamState['truncated'] = true;
            $streamState['collected'] = '';

            return;
        }

        $streamState['collected'] .= $bytes;
    }

    /**
     * @param array{remaining: ?int, isFlate: bool, collected: string, truncated: bool} $streamState
     */
    private function streamPayloadContainsJsName(array $streamState): bool
    {
        if ($streamState['truncated']) {
            return false;
        }

        $decoded = @zlib_decode($streamState['collected'], self::MAX_INFLATED_BYTES);
        if ($decoded === false) {
            return false;
        }

        return $this->regionContainsJsName($decoded, true);
    }

    /**
     * Finds the stream keyword: not part of a longer token such as endstream
     * or a name, and followed by an end-of-line marker (PDF 32000-1 §7.3.8.1).
     */
    private function findStreamKeyword(string $buffer, int $offset): ?int
    {
        if (preg_match('/(?<![A-Za-z0-9#\/])stream(?=\r\n|\n|\r)/', $buffer, $match, PREG_OFFSET_CAPTURE, $offset)) {
            return $match[0][1];
        }

        return null;
    }

    /**
     * Matches /JS and /JavaScript as complete name tokens (PDF 32000-1 §7.3.5):
     * terminated by whitespace or a delimiter, with #xx hex escapes decoded.
     */
    private function regionContainsJsName(string $region, bool $regionIsFinal): bool
    {
        if (!preg_match_all('/\/([^\x00\t\n\f\r ()<>\[\]{}\/%]*)/', $region, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $regionLength = strlen($region);

        foreach ($matches[1] as [$rawName, $nameOffset]) {
            if (!$regionIsFinal && $nameOffset + strlen($rawName) === $regionLength) {
                // the token may continue in the next chunk — it stays in the
                // retained tail and is re-examined once completed
                continue;
            }

            $name = preg_replace_callback(
                '/#([0-9a-fA-F]{2})/',
                static fn (array $hex): string => chr((int) hexdec($hex[1])),
                $rawName
            );

            if ($name === 'JS' || $name === 'JavaScript') {
                return true;
            }
        }

        return false;
    }
}
