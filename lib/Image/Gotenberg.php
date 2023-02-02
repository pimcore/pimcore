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
use Pimcore\Logger;
use Pimcore\Tool\Session;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

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
    public static function convert(string $url, string $outputFile): bool
    {
        $outputPath = dirname($outputFile);
        $filename = basename($outputFile, '.png');

        try {
            $headers = [];
            if (php_sapi_name() !== 'cli') {
                $headers['Cookie'] = Session::useSession(function (AttributeBagInterface $session) {
                    return Session::getSessionName() . '=' . Session::getSessionId();
                });
            }

            $chromium = GotenbergAPI::chromium('gotenberg:3000');

            if ($headers){
                $chromium->extraHttpHeaders($headers);
            }
            $url = str_replace('localhost', 'nginx:80', $url);
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
