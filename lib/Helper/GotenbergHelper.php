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

namespace Pimcore\Helper;

use Exception;
use Gotenberg\Gotenberg as GotenbergAPI;
use GuzzleHttp\Psr7\Request;
use Pimcore\Cache;
use Pimcore\Config;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * @internal
 */
class GotenbergHelper
{
    private static bool $validPing = false;
    private static function healthPing(): bool
    {
        $psr18Client = new Psr18Client(HttpClient::create([
            'timeout' => 2,
            'max_duration' => 2,
        ]));

        $chromeBaseUrl = Config::getSystemConfiguration('gotenberg')['base_url'];
        $request = new Request('GET', $chromeBaseUrl . '/health');

        try {
            $response = GotenbergAPI::send($request, $psr18Client);
            if ($response->getStatusCode() === 200) {
                return true;
            }
        } catch (\Throwable $e) {
            // do nothing
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

        if (!class_exists(GotenbergAPI::class, true)) {
            return false;
        }

        if (self::healthPing()) {
            self::$validPing = true;
            Cache::save(true, 'gotenberg_ping', [], Config::getSystemConfiguration('gotenberg')['ping_cache_ttl']);

            return true;
        }

        return false;
    }
}
