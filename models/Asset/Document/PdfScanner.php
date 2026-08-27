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
 * tokens outside of stream payloads, instead of raw byte matching which flags
 * binary (compressed) stream data by chance (see #16955).
 *
 * This is a heuristic pre-check for the admin preview, not a sanitizer: names
 * hidden inside compressed object streams are not decompressed.
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

    public function __construct(private readonly int $chunkSize = 65536)
    {
    }

    /**
     * @param resource $stream seekable or non-seekable readable stream positioned at the start of the PDF
     */
    public function containsJavaScript($stream): bool
    {
        $buffer = '';
        $inStreamPayload = false;

        do {
            $chunk = feof($stream) ? '' : fread($stream, $this->chunkSize);
            if (is_string($chunk)) {
                $buffer .= $chunk;
            }
            $atEof = $chunk === false || $chunk === '' || feof($stream);

            if ($this->scanBuffer($buffer, $inStreamPayload, $atEof)) {
                return true;
            }
        } while (!$atEof);

        return false;
    }

    /**
     * Consumes the buffer, retaining an unconsumed tail so tokens and keywords
     * split across chunk boundaries are seen once completed by the next read.
     */
    private function scanBuffer(string &$buffer, bool &$inStreamPayload, bool $atEof): bool
    {
        $position = 0;
        $length = strlen($buffer);

        while (true) {
            if ($inStreamPayload) {
                $endstream = strpos($buffer, self::ENDSTREAM_KEYWORD, $position);
                if ($endstream === false) {
                    // everything so far is payload — keep only enough bytes to
                    // recognize a split endstream keyword on the next read
                    $keep = $atEof ? 0 : strlen(self::ENDSTREAM_KEYWORD) - 1;
                    $buffer = $keep > 0 ? substr($buffer, max($position, $length - $keep)) : '';

                    return false;
                }

                $position = $endstream + strlen(self::ENDSTREAM_KEYWORD);
                $inStreamPayload = false;

                continue;
            }

            $streamKeywordStart = $this->findStreamKeyword($buffer, $position);
            $regionEnd = $streamKeywordStart ?? $length;
            $regionIsFinal = $streamKeywordStart === null && $atEof;

            if ($this->regionContainsJsName(substr($buffer, $position, $regionEnd - $position), $regionIsFinal)) {
                return true;
            }

            if ($streamKeywordStart === null) {
                // keep a tail: it may hold the start of a stream keyword or an
                // incomplete name token continued by the next read
                $keep = $atEof ? 0 : self::BOUNDARY_OVERLAP;
                $buffer = $keep > 0 ? substr($buffer, max($position, $length - $keep)) : '';

                return false;
            }

            $position = $streamKeywordStart + strlen(self::STREAM_KEYWORD);
            $inStreamPayload = true;
        }
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
