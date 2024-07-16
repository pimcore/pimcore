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

namespace Pimcore;

use Pimcore;

class Logger
{
    /**
     *
     * @internal
     */
    public static function log(string $message, string $level = 'info', array $context = []): void
    {
        if (Pimcore::hasContainer()) {
            $logger = Pimcore::getContainer()->get('monolog.logger.pimcore');
            $logger->$level($message, $context);
        }
    }

    public static function emergency(string $m, array $context = []): void
    {
        self::log($m, 'emergency', $context);
    }

    public static function emerg(string $m, array $context = []): void
    {
        self::log($m, 'emergency', $context);
    }

    public static function alert(string $m, array $context = []): void
    {
        self::log($m, 'alert', $context);
    }

    public static function critical(string $m, array $context = []): void
    {
        self::log($m, 'critical', $context);
    }

    public static function crit(string $m, array $context = []): void
    {
        self::log($m, 'critical', $context);
    }

    public static function error(string $m, array $context = []): void
    {
        self::log($m, 'error', $context);
    }

    public static function err(string $m, array $context = []): void
    {
        self::log($m, 'error', $context);
    }

    public static function warning(string $m, array $context = []): void
    {
        self::log($m, 'warning', $context);
    }

    public static function warn(string $m, array $context = []): void
    {
        self::log($m, 'warning', $context);
    }

    public static function notice(string $m, array $context = []): void
    {
        self::log($m, 'notice', $context);
    }

    public static function info(string $m, array $context = []): void
    {
        self::log($m, 'info', $context);
    }

    public static function debug(string $m, array $context = []): void
    {
        self::log($m, 'debug', $context);
    }
}
