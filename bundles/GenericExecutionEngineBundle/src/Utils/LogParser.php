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
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\ValueObjects\LogLine;

use function strlen;

/**
 * Encodes and decodes the job run log format that is persisted in a single text column.
 *
 * Entries are delimited by the ASCII record separator (0x1E) instead of a newline, so a
 * newline that is part of a single (multi-line) log message can no longer be mistaken for
 * an entry boundary. Logs written before this format was introduced used a newline as the
 * delimiter and are still read on a best-effort basis via {@see self::parseLegacySegment()}.
 *
 * @internal
 */
final class LogParser
{
    /**
     * ASCII record separator used to delimit individual log entries in the stored column.
     */
    private const ENTRY_SEPARATOR = "\x1e";

    /**
     * Timestamp as produced by DateTimeImmutable::format('c'), tolerating the numeric-offset
     * and Zulu variants that {@see LogLine} is able to parse as well.
     */
    private const TIMESTAMP_PATTERN = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:[+-]\d{2}:?\d{2}|Z)';

    /**
     * Builds the stored representation of a single log entry, ready to be appended to the
     * log column. The record separator that prefixes every entry keeps the delimiter
     * unambiguous even when the message itself contains newlines.
     *
     * Any record-separator bytes that appear inside the message are percent-encoded so that
     * the delimiter remains unambiguous and no payload bytes are lost.
     */
    public function formatEntry(DateTimeImmutable $createdAt, string $message): string
    {
        $message = $this->escapeMessage(trim($message));

        return self::ENTRY_SEPARATOR . $createdAt->format('c') . ': ' . $message;
    }

    /**
     * @return LogLine[]
     */
    public function parse(?string $log): array
    {
        if ($log === null || $log === '') {
            return [];
        }

        $segments = explode(self::ENTRY_SEPARATOR, $log);

        // The first segment holds any legacy, newline-joined entries that were written
        // before the record separator was introduced; it is empty for logs that were
        // written entirely in the current format.
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

        return new LogLine($matches[1], $this->unescapeMessage(substr($segment, strlen($matches[0]))));
    }

    /**
     * Percent-encodes `%` and the record-separator so that those bytes in caller-supplied
     * messages survive a format/parse round trip without ambiguity.
     */
    private function escapeMessage(string $message): string
    {
        return str_replace(['%', self::ENTRY_SEPARATOR], ['%25', '%1E'], $message);
    }

    /**
     * Reverses the encoding applied by {@see self::escapeMessage()}.
     */
    private function unescapeMessage(string $message): string
    {
        return str_replace(['%1E', '%25'], [self::ENTRY_SEPARATOR, '%'], $message);
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
                if ($timestamp !== null) {
                    $entries[] = new LogLine($timestamp, $message);
                }

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

        if ($timestamp !== null) {
            $entries[] = new LogLine($timestamp, $message);
        }

        return $entries;
    }
}
