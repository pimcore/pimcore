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

namespace Pimcore\Helper;

use Exception;
use Gotenberg\Gotenberg as GotenbergAPI;
use Gotenberg\Stream;
use Pimcore\Config;

/**
 * @internal
 */
class GotenbergHelper
{
    private static bool $validPing = false;

    /**
     *
     * @throws Exception
     */
    public static function isAvailable(): bool
    {
        if (self::$validPing) {
            return true;
        }

        if (!class_exists(GotenbergAPI::class, true)) {
            return false;
        }

        $request = null;

        /** @var GotenbergAPI|object $chrome */
        $chrome = GotenbergAPI::chromium(Config::getSystemConfiguration('gotenberg')['base_url']);
        if (method_exists($chrome, 'html')) {
            // gotenberg/gotenberg-php API Client v1
            $request = $chrome->html(Stream::string('dummy.html', '<body></body>'));
        } elseif (method_exists($chrome, 'screenshot')) {
            $request = $chrome->screenshot()->html(Stream::string('dummy.html', '<body></body>'));
        }

        if ($request) {
            try {
                GotenbergAPI::send($request);
                self::$validPing = true;

                return true;
            } catch (Exception $e) {
                // nothing to do
            }
        }

        return false;
    }
}
