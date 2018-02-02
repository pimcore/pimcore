<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Google;

use Pimcore\Analytics\Google\Config\SiteConfigProvider;
use Pimcore\Analytics\Google\Tracker;
use Pimcore\Config\Config as ConfigObject;
use Pimcore\Logger;
use Pimcore\Model;
use Psr\Container\ContainerInterface;

/**
 * @deprecated This class is deprecated and will be removed in future versions. Use the Pimcore\Analytics\Google\Tracker
 *             directly instead.
 */
class Analytics
{
    /**
     * @deprecated Use the Pimcore\Analytics\Google\Config\SiteConfigProvider instead
     *
     * @param Model\Site|null $site
     *
     * @return bool
     */
    public static function isConfigured(Model\Site $site = null)
    {
        return self::getSiteConfigProvider()->isSiteReportingConfigured($site);
    }

    /**
     * @deprecated Use the Pimcore\Analytics\Google\Config\SiteConfigProvider instead
     *
     * @param Model\Site|null $site
     *
     * @return ConfigObject|bool
     */
    public static function getSiteConfig(Model\Site $site = null)
    {
        $config = self::getSiteConfigProvider()->getSiteConfig($site);
        if (!$config) {
            return false; // stick to previous behaviour and return false instead of null
        }

        return $config;
    }

    /**
     * @deprecated Use the Pimcore\Analytics\Google\Tracker instead
     *
     * @param ConfigObject|null $config
     *
     * @return string|null
     */
    public static function getCode($config = null)
    {
        $tracker = self::getTracker();

        if (null !== $config && $config instanceof ConfigObject) {
            return $tracker->generateCodeForSiteConfig($config);
        }

        return $tracker->generateCode();
    }

    /**
     * @deprecated Use the Pimcore\Analytics\Google\Tracker instead
     *
     * @param string $code
     * @param string $where
     */
    public static function addAdditionalCode($code, $where = 'beforeEnd')
    {
        $blockMapping = [
            'beforeInit'     => Tracker::BLOCK_BEFORE_INIT,
            'beforePageview' => Tracker::BLOCK_BEFORE_TRACK,
            'beforeEnd'      => Tracker::BLOCK_AFTER_TRACK,
        ];

        if (!isset($blockMapping[$where])) {
            Logger::error('Invalid code block {block} for additional analytics code', ['block' => $where]);

            return;
        }

        self::getTracker()->addCodePart($code, $blockMapping[$where]);
    }

    /**
     * @deprecated Will be removed in Pimcore 6
     *
     * @param Model\Element\ElementInterface $element
     */
    public static function trackElement(Model\Element\ElementInterface $element)
    {
        Logger::error(__CLASS__ . '::trackElement() is unsupported as of version 2.0.1');
    }

    /**
     * @deprecated Will be removed in Pimcore 6
     *
     * @param $path
     */
    public static function trackPageView($path)
    {
        Logger::error(__CLASS__ . '::trackPageView() is unsupported as of version 2.0.1');
    }

    /**
     * @deprecated Use the Pimcore\Analytics\Google\Tracker instead
     *
     * @param $defaultPath
     */
    public static function setDefaultPath($defaultPath)
    {
        self::getTracker()->setDefaultPath($defaultPath);
    }

    /**
     * @deprecated Use the Pimcore\Analytics\Google\Tracker instead
     *
     * @return string|null
     */
    public static function getDefaultPath()
    {
        return self::getTracker()->getDefaultPath();
    }

    private static function getTracker(): Tracker
    {
        return self::getServiceLocator()->get(Tracker::class);
    }

    private static function getSiteConfigProvider(): SiteConfigProvider
    {
        return self::getServiceLocator()->get(SiteConfigProvider::class);
    }

    private static function getServiceLocator(): ContainerInterface
    {
        return \Pimcore::getContainer()->get('pimcore.analytics.google.fallback_service_locator');
    }
}
