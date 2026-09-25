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

use Generator;

/**
 * Scans a PDF for JavaScript by matching /JS and /JavaScript as proper PDF name
 * tokens, instead of raw byte matching which flags binary (compressed) stream
 * data by chance (see #16955).
 *
 * Stream data is not scanned as text. It ends at the stream's declared /Length
 * or at the next endstream keyword, whichever comes first, and everything
 * after it is scanned as regular file content. Objects — and with them
 * actions — can only live inside stream data when it is an object stream, so
 * a payload is decoded (ASCIIHex, ASCII85 and Flate, sniffed from the data
 * itself) and scanned only when it decodes to an object stream.
 *
 * This is a heuristic pre-check for the admin preview, not a sanitizer: object
 * streams using any other encoding (LZW, RunLength, predictors, encryption)
 * are still left unscanned.
 *
 * @internal
 */
final class PdfScanner
{
    private const STREAM_KEYWORD = 'stream';

    private const ENDSTREAM_KEYWORD = 'endstream';

    private const WHITESPACE = "\x00\t\n\f\r ";

    private const DELIMITERS = '()<>[]{}/%';

    /**
     * Longest byte sequence that may straddle a chunk boundary: a fully
     * hex-escaped /JavaScript name (1 + 10 * 3 bytes) plus a terminating
     * delimiter, or the stream keyword with its EOL marker.
     */
    private const BOUNDARY_OVERLAP = 64;

    /**
     * Text preceding a stream keyword that is kept for reading the stream's
     * dictionary.
     */
    private const MAX_DICTIONARY_BYTES = 65536;

    /**
     * Bounds how much of a single stream payload is buffered for decoding.
     * Beyond it, only a prefix is kept to tell whether it is an object stream.
     */
    private const MAX_STREAM_BUFFER_BYTES = 16 * 1024 * 1024;

    private const STREAM_PREFIX_BYTES = 65536;

    /**
     * Bounds the decoded output scanned per stream, defending against a
     * decompression bomb. An object stream exceeding it can't be cleared and
     * counts as containing JavaScript.
     */
    private const MAX_DECODED_BYTES = 64 * 1024 * 1024;

    /**
     * Decoded output inspected for the object stream header before a payload
     * consisting of nothing but whitespace and comments is scanned anyway.
     */
    private const MAX_HEADER_PROBE_BYTES = 1024 * 1024;

    private const MAX_DECODE_DEPTH = 4;

    private const INFLATE_INPUT_BYTES = 8192;

    public function __construct(private readonly int $chunkSize = 65536)
    {
    }

    /**
     * @param resource $stream seekable or non-seekable readable stream positioned at the start of the PDF
     */
    public function containsJavaScript($stream): bool
    {
        $buffer = '';
        $context = '';
        $streamState = null;

        do {
            $chunk = feof($stream) ? '' : fread($stream, $this->chunkSize);
            if (is_string($chunk)) {
                $buffer .= $chunk;
            }
            $atEof = $chunk === false || $chunk === '' || feof($stream);

            if ($this->scanBuffer($buffer, $context, $streamState, $atEof)) {
                return true;
            }
        } while (!$atEof);

        return false;
    }

    /**
     * Consumes the buffer, retaining an unconsumed tail so tokens and keywords
     * split across chunk boundaries are seen once completed by the next read.
     *
     * @param array{remaining: ?int, payload: string, truncated: bool}|null $streamState
     */
    private function scanBuffer(string &$buffer, string &$context, ?array &$streamState, bool $atEof): bool
    {
        $position = 0;
        $length = strlen($buffer);

        while (true) {
            if ($streamState !== null) {
                $available = $length - $position;
                $remaining = $streamState['remaining'];
                $endstream = strpos($buffer, self::ENDSTREAM_KEYWORD, $position);

                // a reader recovering from a wrong /Length ends the payload at
                // the endstream keyword — whichever end comes first applies
                $complete = true;
                if ($endstream !== false && ($remaining === null || $endstream - $position <= $remaining)) {
                    $consume = $endstream - $position;
                } elseif ($remaining !== null && $remaining <= $available) {
                    $consume = $remaining;
                } elseif ($atEof) {
                    $consume = $available;
                } else {
                    // hold back what may be the start of a split endstream keyword
                    $consume = max(0, $available - (strlen(self::ENDSTREAM_KEYWORD) - 1));
                    $complete = false;
                }

                $this->collectStreamBytes($streamState, substr($buffer, $position, $consume));
                $position += $consume;
                if ($remaining !== null) {
                    $streamState['remaining'] = $remaining - $consume;
                }

                if (!$complete) {
                    $buffer = substr($buffer, $position);

                    return false;
                }

                if ($this->streamPayloadContainsJavaScript($streamState)) {
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
                $cut = $atEof ? $length : max($position, $length - self::BOUNDARY_OVERLAP);
                $context = substr($context . substr($buffer, $position, $cut - $position), -self::MAX_DICTIONARY_BYTES);
                $buffer = substr($buffer, $cut);

                return false;
            }

            $dictionary = $context . $region;
            $context = '';

            // the keyword's EOL marker (guaranteed present by the lookahead
            // in findStreamKeyword) precedes the payload and isn't part of
            // the declared length
            $position = $streamKeywordStart + strlen(self::STREAM_KEYWORD);
            $position += ($buffer[$position] ?? '') === "\r" && ($buffer[$position + 1] ?? '') === "\n" ? 2 : 1;

            $streamState = [
                'remaining' => $this->detectStreamLength($dictionary),
                'payload' => '',
                'truncated' => false,
            ];
        }
    }

    /**
     * Reads a direct /Length from the stream's own dictionary, the one closing
     * right before the stream keyword. Returns null when it is missing, an
     * indirect reference (e.g. `/Length 5 0 R`) or the dictionary can't be
     * read — the payload then ends at the endstream keyword.
     */
    private function detectStreamLength(string $precedingText): ?int
    {
        $entries = $this->readStreamDictionary($precedingText);
        $length = $entries['Length'] ?? null;

        if ($length === null || $length[0] !== 'word' || !ctype_digit($length[1])) {
            return null;
        }

        return (int) $length[1];
    }

    /**
     * @return array<string, array{0: string, 1: string}>|null top-level entries as [token type, value]
     */
    private function readStreamDictionary(string $precedingText): ?array
    {
        $end = $this->skipTrailingWhitespaceAndComments($precedingText);
        if ($end < 2 || substr($precedingText, $end - 2, 2) !== '>>') {
            return null;
        }

        $start = $this->findDictionaryStart($precedingText, $end - 1);
        if ($start === null) {
            return null;
        }

        $tokens = $this->topLevelTokens(substr($precedingText, $start + 2, $end - $start - 4));

        $entries = [];
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] !== 'name' || $i + 1 >= $count) {
                continue;
            }

            $key = $tokens[$i][1];
            $value = $tokens[++$i];
            if ($value[0] === 'word' && ($tokens[$i + 1][0] ?? '') === 'word' && ($tokens[$i + 2][1] ?? '') === 'R') {
                $value = ['reference', ''];
                $i += 2;
            }

            $entries[$key] = $value;
        }

        return $entries;
    }

    private function skipTrailingWhitespaceAndComments(string $text): int
    {
        $end = strlen($text);

        while (true) {
            $end = strlen(rtrim(substr($text, 0, $end), self::WHITESPACE));
            $lineStart = max((int) strrpos(substr($text, 0, $end), "\n"), (int) strrpos(substr($text, 0, $end), "\r"));
            $comment = strpos(substr($text, $lineStart, $end - $lineStart), '%');
            if ($comment === false) {
                return $end;
            }

            $end = $lineStart + $comment;
        }
    }

    /**
     * Walks back from the dictionary's closing `>>` to its matching `<<`,
     * skipping nested dictionaries and strings.
     */
    private function findDictionaryStart(string $text, int $closingAt): ?int
    {
        $depth = 0;

        for ($i = $closingAt; $i >= 0; $i--) {
            $char = $text[$i];

            if ($char === ')' && !$this->isEscaped($text, $i)) {
                $i = $this->findLiteralStringStart($text, $i);
                if ($i === null) {
                    return null;
                }
            } elseif ($char === '>' && $i > 0 && $text[$i - 1] === '>') {
                $depth++;
                $i--;
            } elseif ($char === '>') {
                $i = strrpos(substr($text, 0, $i), '<');
                if ($i === false) {
                    return null;
                }
            } elseif ($char === '<' && $i > 0 && $text[$i - 1] === '<') {
                $i--;
                if (--$depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    private function findLiteralStringStart(string $text, int $closingAt): ?int
    {
        $depth = 0;

        for ($i = $closingAt; $i >= 0; $i--) {
            if (($text[$i] !== '(' && $text[$i] !== ')') || $this->isEscaped($text, $i)) {
                continue;
            }

            $depth += $text[$i] === ')' ? 1 : -1;
            if ($depth === 0) {
                return $i;
            }
        }

        return null;
    }

    private function isEscaped(string $text, int $offset): bool
    {
        $backslashes = strspn(strrev(substr($text, 0, $offset)), '\\');

        return $backslashes % 2 === 1;
    }

    /**
     * Tokenizes a dictionary's body, collapsing nested dictionaries and arrays
     * into a single `composite` token.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function topLevelTokens(string $text): array
    {
        $tokens = [];
        $depth = 0;
        $length = strlen($text);
        $i = 0;

        while ($i < $length) {
            $char = $text[$i];
            $token = null;

            if (str_contains(self::WHITESPACE, $char)) {
                $i++;

                continue;
            }

            if ($char === '%') {
                $i += strcspn($text, "\r\n", $i);
            } elseif ($char === '(') {
                $i = $this->findLiteralStringEnd($text, $i);
                $token = ['string', ''];
            } elseif ($char === '<' && ($text[$i + 1] ?? '') === '<' || $char === '[') {
                $i += $char === '[' ? 1 : 2;
                $depth++;
            } elseif ($char === '>' && ($text[$i + 1] ?? '') === '>' || $char === ']') {
                $i += $char === ']' ? 1 : 2;
                $depth = max(0, $depth - 1);
                $token = $depth === 0 ? ['composite', ''] : null;
            } elseif ($char === '<') {
                $end = strpos($text, '>', $i);
                $i = $end === false ? $length : $end + 1;
                $token = ['string', ''];
            } elseif ($char === '/') {
                $nameLength = strcspn($text, self::WHITESPACE . self::DELIMITERS, $i + 1);
                $token = ['name', $this->decodeName(substr($text, $i + 1, $nameLength))];
                $i += 1 + $nameLength;
            } else {
                $wordLength = max(1, strcspn($text, self::WHITESPACE . self::DELIMITERS, $i));
                $token = ['word', substr($text, $i, $wordLength)];
                $i += $wordLength;
            }

            if ($token !== null && $depth === 0) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    private function findLiteralStringEnd(string $text, int $openingAt): int
    {
        $depth = 0;
        $length = strlen($text);

        for ($i = $openingAt; $i < $length; $i++) {
            if ($text[$i] === '\\') {
                $i++;
            } elseif ($text[$i] === '(') {
                $depth++;
            } elseif ($text[$i] === ')' && --$depth === 0) {
                return $i + 1;
            }
        }

        return $length;
    }

    /**
     * @param array{remaining: ?int, payload: string, truncated: bool} $streamState
     */
    private function collectStreamBytes(array &$streamState, string $bytes): void
    {
        if ($streamState['truncated']) {
            return;
        }

        if (strlen($streamState['payload']) + strlen($bytes) > self::MAX_STREAM_BUFFER_BYTES) {
            // too large to decode in full — keep a prefix to classify it
            $streamState['payload'] = substr($streamState['payload'] . $bytes, 0, self::STREAM_PREFIX_BYTES);
            $streamState['truncated'] = true;

            return;
        }

        $streamState['payload'] .= $bytes;
    }

    /**
     * @param array{remaining: ?int, payload: string, truncated: bool} $streamState
     */
    private function streamPayloadContainsJavaScript(array $streamState): bool
    {
        // a truncated payload can't be cleared once its prefix decodes to an
        // object stream — legitimate object streams are far smaller
        return $this->decodingsContainJavaScript($streamState['payload'], $streamState['truncated'], 0);
    }

    /**
     * Tries the payload as is and every ASCIIHex / ASCII85 / Flate decoding
     * of it, sniffed from the data itself rather than trusting /Filter.
     */
    private function decodingsContainJavaScript(string $data, bool $headerOnly, int $depth): bool
    {
        if ($this->piecesContainJavaScript([$data], $headerOnly)) {
            return true;
        }

        if ($depth >= self::MAX_DECODE_DEPTH) {
            return false;
        }

        $data = ltrim($data, self::WHITESPACE);
        if ($this->hasZlibHeader($data)) {
            return $this->piecesContainJavaScript($this->inflate($data), $headerOnly);
        }

        foreach ([$this->decodeAsciiHex($data), $this->decodeAscii85($data)] as $decoded) {
            if ($decoded !== null && $this->decodingsContainJavaScript($decoded, $headerOnly, $depth + 1)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scans decoded output piece by piece, provided it starts like an object
     * stream (PDF 32000-1 §7.5.7: pairs of integers ahead of the objects).
     *
     * @param iterable<string> $pieces
     */
    private function piecesContainJavaScript(iterable $pieces, bool $headerOnly): bool
    {
        $probe = '';
        $isObjectStream = null;
        $tail = '';
        $decodedBytes = 0;

        foreach ($pieces as $piece) {
            $decodedBytes += strlen($piece);

            if ($isObjectStream === null) {
                $probe .= $piece;
                $isObjectStream = $this->startsLikeObjectStream($probe, false);
                if ($isObjectStream === null) {
                    continue;
                }

                if (!$isObjectStream || $headerOnly) {
                    return $isObjectStream;
                }

                $piece = $probe;
            }

            if ($decodedBytes > self::MAX_DECODED_BYTES) {
                return true;
            }

            $text = $tail . $piece;
            if ($this->regionContainsJsName($text, false)) {
                return true;
            }

            $tail = substr($text, -self::BOUNDARY_OVERLAP);
        }

        if ($isObjectStream === null) {
            $isObjectStream = (bool) $this->startsLikeObjectStream($probe, true);
            if (!$isObjectStream || $headerOnly) {
                return $isObjectStream;
            }

            $tail = $probe;
        }

        return $this->regionContainsJsName($tail, true);
    }

    /**
     * @return bool|null null while the decoded output seen so far is too short to tell
     */
    private function startsLikeObjectStream(string $probe, bool $isComplete): ?bool
    {
        $normalized = (string) preg_replace('/%[^\r\n]*/', ' ', $probe);
        $normalized = ltrim((string) preg_replace('/[\x00\t\n\f\r ]+/', ' ', $normalized), ' ');

        if (preg_match('/^\d+ \d+ /', $normalized)) {
            return true;
        }

        if ($isComplete || !preg_match('/^(\d+( \d*)?)?$/', $normalized)) {
            return false;
        }

        // nothing but whitespace and comments so far: inspect it anyway
        // rather than decoding without bound
        return strlen($probe) > self::MAX_HEADER_PROBE_BYTES ? true : null;
    }

    private function hasZlibHeader(string $data): bool
    {
        if (strlen($data) < 2) {
            return false;
        }

        $method = ord($data[0]);
        $flags = ord($data[1]);

        return ($method & 0x0F) === 8 && ($method >> 4) <= 7 && ($flags & 0x20) === 0
            && (($method << 8) | $flags) % 31 === 0;
    }

    /**
     * Inflates incrementally, skipping the zlib header and not verifying the
     * trailing Adler-32 checksum — readers don't either, and use whatever a
     * corrupt or truncated stream inflates to.
     *
     * @return Generator<int, string>
     */
    private function inflate(string $data): Generator
    {
        $inflateContext = inflate_init(ZLIB_ENCODING_RAW);
        if ($inflateContext === false) {
            return;
        }

        $length = strlen($data);
        for ($offset = 2; $offset < $length; $offset += self::INFLATE_INPUT_BYTES) {
            $output = @inflate_add($inflateContext, substr($data, $offset, self::INFLATE_INPUT_BYTES), ZLIB_SYNC_FLUSH);
            if ($output === false) {
                return;
            }

            if ($output !== '') {
                yield $output;
            }

            if (inflate_get_status($inflateContext) === ZLIB_STREAM_END) {
                return;
            }
        }
    }

    private function decodeAsciiHex(string $data): ?string
    {
        $end = strpos($data, '>');
        $encoded = str_replace(str_split(self::WHITESPACE), '', $end === false ? $data : substr($data, 0, $end));

        if ($encoded === '' || !ctype_xdigit($encoded)) {
            return null;
        }

        return (string) hex2bin(strlen($encoded) % 2 === 1 ? $encoded . '0' : $encoded);
    }

    private function decodeAscii85(string $data): ?string
    {
        $end = strpos($data, '~>');
        $encoded = str_replace(str_split(self::WHITESPACE), '', $end === false ? $data : substr($data, 0, $end));
        $length = strlen($encoded);

        if ($length === 0 || strspn($encoded, implode('', range('!', 'u')) . 'z') !== $length) {
            return null;
        }

        $decoded = '';
        $group = [];
        for ($i = 0; $i < $length; $i++) {
            if ($encoded[$i] === 'z') {
                if ($group !== []) {
                    return null;
                }

                $decoded .= "\0\0\0\0";

                continue;
            }

            $group[] = ord($encoded[$i]) - 33;
            if (count($group) === 5) {
                $bytes = $this->decodeAscii85Group($group);
                if ($bytes === null) {
                    return null;
                }

                $decoded .= $bytes;
                $group = [];
            }
        }

        if ($group !== []) {
            // a final partial group of n characters encodes n - 1 bytes
            $bytes = count($group) > 1 ? $this->decodeAscii85Group(array_pad($group, 5, 84)) : null;
            if ($bytes === null) {
                return null;
            }

            $decoded .= substr($bytes, 0, count($group) - 1);
        }

        return $decoded;
    }

    /**
     * @param list<int> $digits
     */
    private function decodeAscii85Group(array $digits): ?string
    {
        $value = 0;
        foreach ($digits as $digit) {
            $value = $value * 85 + $digit;
        }

        return $value > 0xFFFFFFFF ? null : pack('N', $value);
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

            $name = $this->decodeName($rawName);

            if ($name === 'JS' || $name === 'JavaScript') {
                return true;
            }
        }

        return false;
    }

    private function decodeName(string $rawName): string
    {
        return (string) preg_replace_callback(
            '/#([0-9a-fA-F]{2})/',
            static fn (array $hex): string => chr((int) hexdec($hex[1])),
            $rawName
        );
    }
}
