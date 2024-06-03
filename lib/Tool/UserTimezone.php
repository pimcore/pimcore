<?php
declare(strict_types=1);

namespace Pimcore\Tool;

use Pimcore\Logger;

/**
 * @internal
 */
final class UserTimezone
{
    private static ?string $userTimezone = null;

    public static function setUserTimezone(?string $userTimezone): void
    {
        if ($userTimezone !== null && !in_array($userTimezone, timezone_identifiers_list())) {
            Logger::error('Invalid user timezone: ' . $userTimezone);
            $userTimezone = null;
        }
        self::$userTimezone = $userTimezone;
    }

    public static function getUserTimezone(): ?string
    {
        return self::$userTimezone;
    }

    public static function applyTimezone(\DateTimeInterface $date): \DateTimeInterface
    {
        if (self::getUserTimezone() && method_exists($date, 'setTimezone')) {
            $date = $date->setTimezone(new \DateTimeZone(self::getUserTimezone()));
        }
        return $date;
    }
}
