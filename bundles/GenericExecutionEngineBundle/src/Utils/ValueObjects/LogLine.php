<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Utils\ValueObjects;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * @internal
 */
final class LogLine
{
    private string $logLine;

    private DateTimeImmutable $createdAt;

    public function __construct(string $logLine)
    {
        $this->extract($logLine);
    }

    public function getLogLine(): string
    {
        return $this->logLine;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function extract(string $logLine): void
    {
        $logLine = trim($logLine);
        $dateTimeString = substr($logLine, 0, 25);
        $log = substr($logLine, 27);
        $dateTime = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $dateTimeString);

        if ($dateTime === false) {
            throw new InvalidArgumentException('Invalid Time Format given');
        }

        $this->createdAt = $dateTime;
        $this->logLine = $log;
    }
}
