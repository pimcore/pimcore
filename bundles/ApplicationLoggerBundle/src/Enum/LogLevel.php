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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Enum;

use Pimcore\Bundle\ApplicationLoggerBundle\Exception\LogLevelNotFoundException;

enum LogLevel: int
{
    case Emergency = 1;
    case Alert = 2;
    case Critical = 3;
    case Error = 4;
    case Warning = 5;
    case Notice = 6;
    case Info = 7;
    case Debug = 8;

    public static function getLogLevel(string|int $value): self
    {
        if (is_int($value)) {
            $enum = static::tryFrom($value);
            if ($enum !== null) {
                return $enum;
            }

            throw new LogLevelNotFoundException();
        }

        foreach (static::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        throw new LogLevelNotFoundException();
    }
}
