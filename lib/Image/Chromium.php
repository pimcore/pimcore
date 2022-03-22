<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Image;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\Cookie;
use Pimcore\Tool\Console;
use Pimcore\Tool\Session;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 */
class Chromium
{
    /**
     * @return bool
     */
    public static function isSupported()
    {
        return (bool)self::getChromiumBinary() && class_exists(BrowserFactory::class);
    }

    /**
     * @return bool
     */
    public static function getChromiumBinary()
    {
        foreach (['chromium', 'chrome'] as $app) {
            $chromium = \Pimcore\Tool\Console::getExecutable($app);
            if ($chromium) {
                return $chromium;
            }
        }

        return false;
    }

    /**
     * @param string $url
     * @param string $outputFile
     * @param string $windowSize
     *
     * @return bool
     * @throws \Exception
     */
    public static function convert(string $url, string $outputFile, string $windowSize = '1280,1024'): bool
    {
        $browserFactory = new BrowserFactory(self::getChromiumBinary());

        // starts headless chrome
        $browser = $browserFactory->createBrowser([
                'noSandbox' => file_exists('/.dockerenv'),
                'startupTimeout' => 120,
                'windowSize' => explode(',', $windowSize),
        ]);

        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation();

            if (php_sapi_name() !== 'cli') {
                $sessionData = Session::useSession(function (AttributeBagInterface $session) {
                    return ['name' => Session::getSessionName(), 'id' => Session::getSessionId()];
                });

                $page->setCookies([
                    Cookie::create($sessionData['name'], (string)$sessionData['id'], [
                        "session" => true,
                        'secure' => false,
                        'sameSite' => "Strict",
                        'sameParty' => true,
                    ])
                ])->await();
            }

            $page->screenshot([
                'captureBeyondViewport' => true,
                'clip' => $page->getFullPageClip(),
            ])->saveToFile($outputFile);

            return true;
        } finally {
            $browser->close();

            return false;
        }
    }
}
