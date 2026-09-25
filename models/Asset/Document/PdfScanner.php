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
 * Stream data is not scanned as text. A direct /Length is trusted outright
 * for where the payload ends — never by searching for the endstream keyword
 * inside it, since a genuine payload can be made to contain that exact byte
 * sequence (e.g. a raw/stored deflate block) and stopping there would
 * truncate the decode early. Everything past a fulfilled declared length is
 * scanned like ordinary file content, whether or not it happens to contain
 * the endstream keyword itself — a short declared length doesn't leave a gap
 * that's skipped unscanned the way it used to. Only when the file runs out
 * before the declared length is satisfied is it treated as unfulfillable,
 * recovering the true payload from the literal endstream keyword instead.
 * The same recovery applies when the length is missing or an indirect
 * reference to begin with, which this heuristic scanner can't resolve.
 *
 * Objects — and with them actions — can only live inside stream data when it
 * is an object stream. The dictionary's own /Type decides that whenever it is
 * present and readable; only when it is missing or an indirect reference does
 * a structural fallback (PDF 32000-1 §7.5.7: pairs of integers ahead of the
 * objects) apply, so a stream explicitly typed as something else (an image,
 * for instance) is never scanned no matter what its payload happens to
 * contain. Every payload resolved this way is decoded (ASCIIHex, ASCII85 and
 * Flate, sniffed from the data itself) before it is scanned.
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
     * dictionary. Bounded so an attacker sending endless non-stream content
     * can't grow this without limit; real dictionaries are a tiny fraction
     * of this size, so a bigger one is treated as unreadable — the safe
     * direction, since that just means the length falls back to unknown.
     */
    private const MAX_DICTIONARY_BYTES = 1024 * 1024;

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
     * @param array{remaining: ?int, type: ?string, payload: string, truncated: bool}|null $streamState
     */
    private function scanBuffer(string &$buffer, string &$context, ?array &$streamState, bool $atEof): bool
    {
        $position = 0;
        $length = strlen($buffer);

        while (true) {
            if ($streamState !== null) {
                if ($streamState['remaining'] !== null) {
                    // a declared length is trusted outright, and never by
                    // searching for the endstream keyword inside it — a
                    // genuine payload can be made to contain that byte
                    // sequence (e.g. a raw/stored deflate block), and
                    // stopping at it would truncate the decode early
                    $consume = min($length - $position, $streamState['remaining']);
                    $this->collectStreamBytes($streamState, substr($buffer, $position, $consume));
                    $position += $consume;
                    $streamState['remaining'] -= $consume;

                    if ($streamState['remaining'] > 0) {
                        if (!$atEof) {
                            // payload continues beyond this chunk
                            $buffer = '';

                            return false;
                        }

                        // the file ended before the declared length could be
                        // fulfilled, so it can't be trusted after all. If the
                        // real endstream is already among what was
                        // collected, the true payload is everything before
                        // it; resume normal scanning right after it, the
                        // same as a too-long length recovers below. If it
                        // isn't there either, there's nothing left in the
                        // file to recover a boundary from.
                        $payload = $streamState['payload'];
                        $endstream = $streamState['truncated'] ? false : strpos($payload, self::ENDSTREAM_KEYWORD);

                        if ($endstream === false) {
                            $buffer = '';

                            return false;
                        }

                        $streamState['payload'] = substr($payload, 0, $endstream);
                        $buffer = substr($payload, $endstream + strlen(self::ENDSTREAM_KEYWORD));
                        $length = strlen($buffer);
                        $position = 0;

                        if ($this->streamPayloadContainsJavaScript($streamState)) {
                            return true;
                        }

                        $streamState = null;

                        continue;
                    }

                    // the declared length was fully consumed: trust it
                    // outright and resume normal scanning right after it,
                    // regardless of what follows. A too-short declared length
                    // means everything past it is, by definition, no longer
                    // stream data — this is what closes the smuggling bypass,
                    // the gap is scanned like any other file content instead
                    // of being skipped as if it were still part of the stream.
                    if ($this->streamPayloadContainsJavaScript($streamState)) {
                        return true;
                    }

                    $streamState = null;

                    continue;
                }

                // length unknown (missing or an indirect reference) or given
                // up on above: fall back to finding the literal endstream
                // keyword, exactly as this class always did before it
                // inspected streams — the payload found this way is still
                // decoded and checked like any other, just without a
                // validated exact boundary
                $endstream = strpos($buffer, self::ENDSTREAM_KEYWORD, $position);
                if ($endstream === false) {
                    $keep = $atEof ? 0 : strlen(self::ENDSTREAM_KEYWORD) - 1;
                    $cut = $atEof ? $length : max($position, $length - $keep);
                    $this->collectStreamBytes($streamState, substr($buffer, $position, $cut - $position));
                    $buffer = substr($buffer, $cut);

                    return false;
                }

                $this->collectStreamBytes($streamState, substr($buffer, $position, $endstream - $position));
                $position = $endstream + strlen(self::ENDSTREAM_KEYWORD);

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

            $entries = $this->readStreamDictionary($dictionary);

            $streamState = [
                'remaining' => $this->extractStreamLength($entries),
                'type' => $this->extractStreamType($entries),
                'payload' => '',
                'truncated' => false,
            ];
        }
    }

    /**
     * Reads a direct /Length from the stream's own dictionary. Returns null
     * when it is missing, an indirect reference (e.g. `/Length 5 0 R`) or the
     * dictionary can't be read — the caller then falls back to treating the
     * stream exactly as before this class used /Length at all.
     */
    private function extractStreamLength(?array $entries): ?int
    {
        $value = $entries['Length'] ?? null;
        if ($value === null || $value[0] !== 'word') {
            return null;
        }

        $digits = $value[1];
        if ($digits !== '' && ($digits[0] === '+')) {
            // PDF integers may carry an optional leading sign (PDF 32000-1
            // §7.3.3); a negative length is nonsensical and left unknown
            $digits = substr($digits, 1);
        }

        return $digits !== '' && ctype_digit($digits) ? (int) $digits : null;
    }

    /**
     * Reads the stream's declared /Type, decoded like any other name. Null
     * when it is missing or an indirect reference — the caller then falls
     * back to recognizing an object stream by its own structure instead.
     */
    private function extractStreamType(?array $entries): ?string
    {
        $value = $entries['Type'] ?? null;

        return $value !== null && $value[0] === 'name' ? $value[1] : null;
    }

    /**
     * Reads the top-level entries of the dictionary that closes right before
     * the stream keyword (the last one at nesting depth 0 in $precedingText,
     * with nothing but whitespace or comments — correctly recognized even
     * inside a literal string — following it).
     *
     * @return array<string, array{0: string, 1: string}>|null entries as [token type, value]
     */
    private function readStreamDictionary(string $precedingText): ?array
    {
        $dictionary = $this->findLastTopLevelDictionary($precedingText);
        if ($dictionary === null) {
            return null;
        }

        $tokens = $this->topLevelTokens(
            substr($precedingText, $dictionary['start'] + 2, $dictionary['end'] - $dictionary['start'] - 4)
        );

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

    /**
     * Forward scan for the last dictionary that opens and closes at nesting
     * depth 0, with nothing else at depth 0 following its close. Comments and
     * literal strings are recognized correctly no matter what they contain
     * (including a '%' or a nested, escaped ')'), so neither can be mistaken
     * for structure — the same bug class that #16955 already ruled out of
     * plain byte scanning.
     *
     * @return array{start: int, end: int}|null
     */
    private function findLastTopLevelDictionary(string $text): ?array
    {
        $depth = 0;
        $length = strlen($text);
        $i = 0;
        /** @var list<int> $starts */
        $starts = [];
        $last = null;

        while ($i < $length) {
            $char = $text[$i];

            if (str_contains(self::WHITESPACE, $char)) {
                $i++;

                continue;
            }

            if ($char === '%') {
                $i += strcspn($text, "\r\n", $i);

                continue;
            }

            if ($depth === 0) {
                // real content at top level invalidates a previously found
                // candidate — it wasn't the last thing here after all
                $last = null;
            }

            if ($char === '(') {
                $i = $this->findLiteralStringEnd($text, $i);
            } elseif ($char === '<' && ($text[$i + 1] ?? '') === '<') {
                $starts[] = $i;
                $depth++;
                $i += 2;
            } elseif ($char === '>' && ($text[$i + 1] ?? '') === '>') {
                $i += 2;
                $start = array_pop($starts);
                if ($start !== null && $depth > 0 && --$depth === 0) {
                    $last = ['start' => $start, 'end' => $i];
                }
            } elseif ($char === '[') {
                $depth++;
                $i++;
            } elseif ($char === ']') {
                $i++;
                $depth = max(0, $depth - 1);
            } elseif ($char === '<') {
                $end = strpos($text, '>', $i);
                $i = $end === false ? $length : $end + 1;
            } else {
                $i += max(1, strcspn($text, self::WHITESPACE . self::DELIMITERS, $i));
            }
        }

        return $last;
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
     * @param array{remaining: ?int, type: ?string, payload: string, truncated: bool} $streamState
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
     * @param array{remaining: ?int, type: ?string, payload: string, truncated: bool} $streamState
     */
    private function streamPayloadContainsJavaScript(array $streamState): bool
    {
        // a truncated payload can't be cleared once its prefix decodes to an
        // object stream — legitimate object streams are far smaller
        return $this->decodingsContainJavaScript($streamState['payload'], $streamState['truncated'], 0, $streamState['type']);
    }

    /**
     * Tries the payload as is and every ASCIIHex / ASCII85 / Flate decoding
     * of it, sniffed from the data itself rather than trusting /Filter.
     */
    private function decodingsContainJavaScript(string $data, bool $headerOnly, int $depth, ?string $type): bool
    {
        if ($this->piecesContainJavaScript([$data], $headerOnly, $type)) {
            return true;
        }

        if ($depth >= self::MAX_DECODE_DEPTH) {
            return false;
        }

        $data = ltrim($data, self::WHITESPACE);
        if ($this->hasZlibHeader($data)) {
            return $this->piecesContainJavaScript($this->inflate($data), $headerOnly, $type);
        }

        foreach ([$this->decodeAsciiHex($data), $this->decodeAscii85($data)] as $decoded) {
            if ($decoded !== null && $this->decodingsContainJavaScript($decoded, $headerOnly, $depth + 1, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scans decoded output piece by piece. Only an object stream can hold
     * objects and thus an action: when the dictionary's own /Type says so,
     * or says otherwise, that decides it outright; only when /Type is
     * missing or unresolvable does whether the output starts like one (PDF
     * 32000-1 §7.5.7: pairs of integers ahead of the objects) decide instead.
     *
     * @param iterable<string> $pieces
     */
    private function piecesContainJavaScript(iterable $pieces, bool $headerOnly, ?string $type): bool
    {
        if ($type !== null && $type !== 'ObjStm') {
            return false;
        }

        if ($type === 'ObjStm' && $headerOnly) {
            return true;
        }

        $probe = '';
        $confirmed = $type === 'ObjStm';
        $tail = '';
        $decodedBytes = 0;

        foreach ($pieces as $piece) {
            $decodedBytes += strlen($piece);

            if (!$confirmed) {
                $probe .= $piece;
                $isObjectStream = $this->startsLikeObjectStream($probe, false);
                if ($isObjectStream === null) {
                    continue;
                }

                if (!$isObjectStream || $headerOnly) {
                    return $isObjectStream;
                }

                $confirmed = true;
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

        if (!$confirmed) {
            $isObjectStream = $this->startsLikeObjectStream($probe, true);
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
