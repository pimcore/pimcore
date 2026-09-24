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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Utils;

use DateTimeImmutable;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\InvalidLogLineException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\ValueObjects\LogLine;

use function strlen;

/**
 * Encodes and decodes the job run log format that is persisted in a single text column.
 *
 * Each entry is framed by a short, versioned delimiter ({@see self::ENTRY_DELIMITER}) instead
 * of a bare newline, so a newline that is part of a single (multi-line) log message can no
 * longer be mistaken for an entry boundary.
 *
 * The delimiter deliberately wraps a version token between two record-separator bytes rather
 * than being a single 0x1E byte: logs written before this format existed were stored verbatim
 * and may already contain a stray 0x1E, so a bare separator would split such legacy payloads
 * and silently drop everything after the first stray byte. Framing the version token makes an
 * accidental collision with real legacy content effectively impossible.
 *
 * Logs written before this format was introduced used a newline as the delimiter and are still
 * read on a best-effort basis via {@see self::parseLegacySegment()}; because they never contain
 * the framed delimiter, their entire content (including any raw 0x1E bytes) is treated as legacy.
 *
 * @internal
 */
final class LogParser
{
    /**
     * ASCII record separator (0x1E). Escaped inside message payloads so that a payload can never
     * reproduce the entry delimiter, and used as the framing byte of {@see self::ENTRY_DELIMITER}.
     */
    private const RECORD_SEPARATOR = "\x1e";

    /**
     * Version token identifying this storage format. Bump it if the framing ever changes.
     */
    private const FORMAT_VERSION = 'GEEv1';

    /**
     * Versioned frame that delimits (and prefixes) every entry in the current format. Wrapping the
     * version token in record separators keeps it identifiable and prevents arbitrary legacy bytes
     * from being treated as an entry boundary.
     */
    private const ENTRY_DELIMITER = self::RECORD_SEPARATOR . self::FORMAT_VERSION . self::RECORD_SEPARATOR;

    /**
     * Timestamp as produced by DateTimeImmutable::format('c'), tolerating the numeric-offset
     * and Zulu variants that {@see LogLine} is able to parse as well.
     */
    private const TIMESTAMP_PATTERN = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:[+-]\d{2}:?\d{2}|Z)';

    /**
     * Builds the stored representation of a single log entry, ready to be appended to the
     * log column. The versioned delimiter that prefixes every entry keeps the boundary
     * unambiguous even when the message itself contains newlines or a stray record separator.
     *
     * Any record-separator bytes that appear inside the message are percent-encoded so that
     * the payload can never reproduce the delimiter and no payload bytes are lost.
     */
    public function formatEntry(DateTimeImmutable $createdAt, string $message): string
    {
        $message = $this->escapeMessage(trim($message));

        return self::ENTRY_DELIMITER . $createdAt->format('c') . ': ' . $message;
    }

    /**
     * @return LogLine[]
     */
    public function parse(?string $log): array
    {
        if ($log === null || $log === '') {
            return [];
        }

        $segments = explode(self::ENTRY_DELIMITER, $log);

        // The first segment holds any legacy, newline-joined entries that were written before
        // the framed delimiter was introduced; it is empty for logs written entirely in the
        // current format. Because we split on the full versioned frame (never a bare 0x1E),
        // a legacy payload containing a stray record separator stays intact in this segment.
        $legacySegment = array_shift($segments);
        $entries = $this->parseLegacySegment($legacySegment);

        // Every remaining segment is exactly one entry, so its message can be kept verbatim
        // (including any newlines) without further guessing at entry boundaries.
        foreach ($segments as $segment) {
            $entry = $this->createLogLine($segment);
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function createLogLine(string $segment): ?LogLine
    {
        if (preg_match('/^(' . self::TIMESTAMP_PATTERN . '): /', $segment, $matches) !== 1) {
            return null;
        }

        try {
            return new LogLine($matches[1], $this->unescapeMessage(substr($segment, strlen($matches[0]))));
        } catch (InvalidLogLineException) {
            // The timestamp is shaped like a valid one but is out of range (e.g. Feb 31).
            // Skip this segment rather than aborting parsing of the remaining log.
            return null;
        }
    }

    /**
     * Percent-encodes `%` and the record-separator so that those bytes in caller-supplied
     * messages survive a format/parse round trip and can never reproduce the entry delimiter.
     */
    private function escapeMessage(string $message): string
    {
        return str_replace(['%', self::RECORD_SEPARATOR], ['%25', '%1E'], $message);
    }

    /**
     * Reverses the encoding applied by {@see self::escapeMessage()}.
     */
    private function unescapeMessage(string $message): string
    {
        return str_replace(['%1E', '%25'], [self::RECORD_SEPARATOR, '%'], $message);
    }

    /**
     * @return LogLine[]
     */
    private function parseLegacySegment(string $segment): array
    {
        if (trim($segment) === '') {
            return [];
        }

        /** @var LogLine[] $entries */
        $entries = [];
        $timestamp = null;
        $message = '';

        foreach (explode("\n", $segment) as $line) {
            $line = rtrim($line, "\r");

            if (preg_match('/^(' . self::TIMESTAMP_PATTERN . '): /', $line, $matches) === 1) {
                $this->appendLegacyEntry($entries, $timestamp, $message);

                $timestamp = $matches[1];
                $message = substr($line, strlen($matches[0]));

                continue;
            }

            // A line that does not start with a timestamp is a continuation of the current
            // multi-line message, so re-attach the newline that explode() removed.
            if ($timestamp !== null) {
                $message .= "\n" . $line;
            }
        }

        $this->appendLegacyEntry($entries, $timestamp, $message);

        return $entries;
    }

    /**
     * Appends a reconstructed legacy entry, skipping it when the timestamp is absent or
     * out of range so a single corrupt entry cannot abort parsing of the remaining log.
     *
     * @param LogLine[] $entries
     */
    private function appendLegacyEntry(array &$entries, ?string $timestamp, string $message): void
    {
        if ($timestamp === null) {
            return;
        }

        try {
            $entries[] = new LogLine($timestamp, $message);
        } catch (InvalidLogLineException) {
            // best-effort: drop the malformed legacy entry, keep parsing the rest
        }
    }
}
