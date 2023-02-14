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

namespace Pimcore\Image;

use Gotenberg\Gotenberg as GotenbergAPI;
use Pimcore\Config;
use Pimcore\Logger;

/**
 * @internal
 */
class Gotenberg
{
    public static function isSupported(): bool
    {
        return class_exists(GotenbergAPI::class);
    }

    /**
     * @throws \Exception
     */
    public static function convert(string $url, string $outputFile, ?string $sessionName = null, ?string $sessionId = null, string $windowSize = '1280,1024'): bool
    {
        $outputPath = dirname($outputFile);
        $filename = basename($outputFile, '.png');

        try {
            $headers = [];
            if (php_sapi_name() !== 'cli') {
                if (null !== $sessionId && null !== $sessionName) {
                    $headers['Cookie'] = $sessionName . '=' . $sessionId;
                }
            }

            $gotenbergBaseUrl = Config::getSystemConfiguration('gotenberg')['base_url'];
            $chromium = GotenbergAPI::chromium($gotenbergBaseUrl);

            if (!empty($headers)) {
                $chromium->extraHttpHeaders($headers);
            }

            list($paperWidth, $paperHeight) = explode(',', $windowSize);

            // the PDF paper size works in inches, 96 is to convert pixels to inches
            $chromium->paperSize((int)$paperWidth / 96, (int)$paperHeight / 96);
            $chromium->printBackground();
            $chromium->emulateScreenMediaType();
            $chromium->nativePageRanges('1');

            $request = $chromium->outputFilename($filename)->url($url);
            GotenbergAPI::save($request, $outputPath);

        } catch (\Throwable $e) {
            Logger::debug('Could not create image from url: ' . $url);
            Logger::debug((string) $e);

            return false;
        }

        return true;
    }
}
