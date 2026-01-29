<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Helper;

use Exception;
use Gotenberg\Gotenberg as GotenbergAPI;
use Pimcore\Cache;
use Pimcore\Config;

/**
 * @internal
 */
class GotenbergHelper
{
    private static bool $validPing = false;

    private static function healthPing(): bool
    {
        $gotenbergBaseUrl = Config::getSystemConfiguration('gotenberg')['base_url'];
        if ($gotenbergBaseUrl) {
            try {
                $ch = curl_init(rtrim($gotenbergBaseUrl, '/') . '1/health');

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 2,
                ]);

                curl_exec($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                return $status === 200;
            } catch (\Throwable $e) {
                return false;
            }
        }
        return false;
    }

    /**
     *
     * @throws Exception
     */
    public static function isAvailable(): bool
    {
        if (self::$validPing) {
            return true;
        }

        if (Cache::load('gotenberg_ping') === true) {
            self::$validPing = true;
            return true;
        }

        if (Cache::load('gotenberg_inactive') === true) {
            self::$validPing = false;
            return false;
        }

        $ttl = Config::getSystemConfiguration('gotenberg')['ping_cache_ttl'];

        if (!class_exists(GotenbergAPI::class, true)) {
            Cache::save(true, 'gotenberg_inactive', [], $ttl);
            return false;
        }

        if (self::healthPing()) {
            self::$validPing = true;
            Cache::save(true, 'gotenberg_ping', [], $ttl);
            return true;
        }

        Cache::save(true, 'gotenberg_inactive', [], $ttl);
        return false;
    }
}
