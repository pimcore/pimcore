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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Utils\ValueObjects;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\InvalidLogLineException;

/**
 * Immutable representation of a single job run log entry: the moment it was created
 * plus its (potentially multi-line) message. Segmentation of the raw log column into
 * individual entries is the responsibility of the LogParser.
 *
 * @internal
 */
final class LogLine
{
    /**
     * Supported timestamp formats, each paired with an optional timezone to force.
     * ATOM and the numeric-offset variant carry their own offset; the Zulu variant
     * is anchored to UTC.
     *
     * @var list<array{string, string|null}>
     */
    private const SUPPORTED_FORMATS = [
        [DateTimeInterface::ATOM, null],
        ['Y-m-d\TH:i:sO', null],
        ['Y-m-d\TH:i:s\Z', 'UTC'],
    ];

    private readonly DateTimeImmutable $createdAt;

    private readonly string $logLine;

    /**
     * @throws InvalidLogLineException if $dateTime is not a supported timestamp format
     */
    public function __construct(string $dateTime, string $logLine)
    {
        $this->createdAt = $this->parseDateTime($dateTime);
        $this->logLine = $logLine;
    }

    public function getLogLine(): string
    {
        return $this->logLine;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @throws InvalidLogLineException
     */
    private function parseDateTime(string $dateTime): DateTimeImmutable
    {
        // createFromFormat() overflows out-of-range fields (e.g. 2024-02-31 becomes a
        // March date) and only records a *warning* while still returning a valid object.
        // A candidate format is therefore accepted only when it both parses and reports
        // no warnings or errors; otherwise the timestamp is treated as invalid so a
        // corrupt value can never masquerade as an accurate creation time.
        foreach (self::SUPPORTED_FORMATS as [$format, $timezone]) {
            $parsed = $timezone === null
                ? DateTimeImmutable::createFromFormat($format, $dateTime)
                : DateTimeImmutable::createFromFormat($format, $dateTime, new DateTimeZone($timezone));

            $errors = DateTimeImmutable::getLastErrors();
            $hasIssues = $errors !== false
                && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

            if ($parsed !== false && !$hasIssues) {
                return $parsed;
            }
        }

        throw new InvalidLogLineException(
            sprintf('Invalid log line date time format given: "%s".', $dateTime)
        );
    }
}
