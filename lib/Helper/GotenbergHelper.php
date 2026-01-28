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
use Gotenberg\Stream;
use GuzzleHttp\Psr7\Request;
use Pimcore\Cache;
use Pimcore\Config;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;

/**
 * @internal
 */
class GotenbergHelper
{
    private static bool $validPing = false;
    private static function healthPing(): ?ResponseInterface
    {
        $chromeBaseUrl = Config::getSystemConfiguration('gotenberg')['base_url'];

        $request = new Request(
            'GET',
            $chromeBaseUrl . '/health'
        );

        $client = new GuzzleAdapter(
            new GuzzleClient([
                'connect_timeout' => 1,
                'timeout' => 2,
            ])
        );

        try {
            return GotenbergAPI::send($request, $client);
        } catch (\Throwable $e) {
            return null;
        }
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

        if (self::healthPing()){
            self::$validPing = true;
            Cache::save(true, 'gotenberg_ping', [], Config::getSystemConfiguration('gotenberg')['ping_cache_ttl']);

            return true;
        }


        return false;
    }
}
