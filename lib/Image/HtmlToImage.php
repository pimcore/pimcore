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

namespace Pimcore\Image;

use Exception;
use Gotenberg\Gotenberg as GotenbergAPI;
use Pimcore\Config;
use Pimcore\Helper\GotenbergHelper;

/**
 * @internal
 */
class HtmlToImage
{
    private static ?string $supportedAdapter = null;

    public static function isSupported(): bool
    {
        return (bool) self::getSupportedAdapter();
    }

    private static function getSupportedAdapter(): string
    {
        if (self::$supportedAdapter !== null) {
            return self::$supportedAdapter;
        }

        self::$supportedAdapter = '';

        if (GotenbergHelper::isAvailable()) {
            self::$supportedAdapter = 'gotenberg';
        }

        return self::$supportedAdapter;
    }

    /**
     * @throws Exception
     */
    public static function convert(string $url, string $outputFile, ?string $sessionName = null, ?string $sessionId = null, string $windowSize = '1280,1024'): bool
    {
        $adapter = self::getSupportedAdapter();
        if ($adapter === 'gotenberg') {
            return self::convertGotenberg(...func_get_args());
        }

        return false;
    }

    public static function convertGotenberg(string $url, string $outputFile, ?string $sessionName = null, ?string $sessionId = null, string $windowSize = '1280,1024'): bool
    {
        try {
            $request = GotenbergAPI::chromium(Config::getSystemConfiguration('gotenberg')['base_url']);
            $sizes = explode(',', $windowSize);
            $urlResponse = $request->screenshot()
                ->width((int) $sizes[0])
                ->height((int) $sizes[1])
                ->png()
                ->url($url);

            $file = GotenbergAPI::save($urlResponse, PIMCORE_SYSTEM_TEMP_DIRECTORY);

            return rename(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $file, $outputFile);
        } catch (Exception) {
            // nothing to do
        }

        return false;
    }
}
