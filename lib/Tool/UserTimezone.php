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

namespace Pimcore\Tool;

use DateTimeInterface;
use DateTimeZone;
use Pimcore\Logger;

/**
 * @internal
 */
final class UserTimezone
{
    private static ?string $userTimezone = null;

    public static function setUserTimezone(?string $userTimezone): void
    {
        if ($userTimezone !== null && !in_array($userTimezone, timezone_identifiers_list(DateTimeZone::ALL_WITH_BC))) {
            Logger::error('Invalid user timezone: ' . $userTimezone);
            $userTimezone = null;
        }
        self::$userTimezone = $userTimezone;
    }

    public static function getUserTimezone(): ?string
    {
        return self::$userTimezone;
    }

    public static function applyTimezone(DateTimeInterface $date): DateTimeInterface
    {
        if (self::getUserTimezone() && method_exists($date, 'setTimezone')) {
            $date = $date->setTimezone(new DateTimeZone(self::getUserTimezone()));
        }

        return $date;
    }
}
