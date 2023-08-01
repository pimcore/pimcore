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

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use Pimcore\Logger;
use Pimcore\Tool\Console;

/**
 * @internal
 */
class Chromium
{
    public static function isSupported(): bool
    {
        if (!class_exists(BrowserFactory::class)) {
            return false;
        }

        $chromiumUri = \Pimcore\Config::getSystemConfiguration('chromium')['uri'];
        if (!empty($chromiumUri)) {
            try {
                return (new Connection($chromiumUri))->connect();
            } catch (\Exception $e) {
                Logger::debug((string) $e);

                return false;
            }
        }

        return (bool) self::getChromiumBinary();
    }

    public static function getChromiumBinary(): ?string
    {
        foreach (['chromium', 'chrome'] as $app) {
            $chromium = Console::getExecutable($app);
            if ($chromium) {
                return $chromium;
            }
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    public static function convert(string $url, string $outputFile, ?string $sessionName = null, ?string $sessionId = null, string $windowSize = '1280,1024'): bool
    {
        $chromiumUri = \Pimcore\Config::getSystemConfiguration('chromium')['uri'];
        if (!empty($chromiumUri)) {
            try {
                $browser = BrowserFactory::connectToBrowser($chromiumUri);
            } catch (\Exception $e) {
                Logger::debug((string) $e);

                return false;
            }
        } else {
            $binary = self::getChromiumBinary();
            if (!$binary) {
                return false;
            }
            $browserFactory = new BrowserFactory($binary);
            $browser = $browserFactory->createBrowser([
                'noSandbox' => file_exists('/.dockerenv'),
                'startupTimeout' => 120,
                'windowSize' => explode(',', $windowSize),
            ]);
        }

        try {
            $headers = [];
            if (null !== $sessionId && null !== $sessionName) {
                $headers['Cookie'] = $sessionName . '=' . $sessionId;
            }

            $page = $browser->createPage();

            if (!empty($headers)) {
                $page->getSession()->sendMessageSync(new Message(
                    'Network.setExtraHTTPHeaders',
                    ['headers' => $headers]
                ));
            }

            $page->navigate($url)->waitForNavigation();

            $page->screenshot([
                'captureBeyondViewport' => true,
                'clip' => $page->getFullPageClip(),
            ])->saveToFile($outputFile);
        } catch (\Throwable $e) {
            Logger::debug('Could not create image from url ' . $url . ': ' . $e);

            return false;
        } finally {
            $browser->close();
        }

        return true;
    }
}
