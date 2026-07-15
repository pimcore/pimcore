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
        $parsed = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $dateTime)
            ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sO', $dateTime)
            ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $dateTime, new DateTimeZone('UTC'));

        if ($parsed === false) {
            throw new InvalidLogLineException(
                sprintf('Invalid log line date time format given: "%s".', $dateTime)
            );
        }

        return $parsed;
    }
}
