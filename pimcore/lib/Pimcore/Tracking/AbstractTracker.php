<?php

declare(strict_types=1);

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

namespace Pimcore\Tracking;

use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Site;

abstract class AbstractTracker implements TrackerInterface
{
    const CONFIG_KEY_MAIN_DOMAIN = 'default';

    /**
     * @var SiteResolver
     */
    private $siteResolver;

    public function __construct(SiteResolver $siteResolver)
    {
        $this->siteResolver = $siteResolver;
    }

    /**
     * Get code for the current site if any/fall back to main domain
     *
     * @param Site|null $site
     *
     * @return null|string Null if no tracking is configured
     */
    public function getCode(Site $site = null)
    {
        if (null !== $site) {
            return $this->getSiteCode($site);
        } elseif ($this->siteResolver->isSiteRequest()) {
            $site = $this->siteResolver->getSite();

            return $this->getSiteCode($site);
        }

        return $this->getMainCode();
    }

    /**
     * Get code for main domain
     *
     * @return null|string
     */
    public function getMainCode()
    {
        return $this->generateCode(self::CONFIG_KEY_MAIN_DOMAIN);
    }

    /**
     * Get code for a specific site
     *
     * @param Site $site
     *
     * @return null|string
     */
    public function getSiteCode(Site $site)
    {
        return $this->generateCode($this->getSiteConfigKey($site), $site);
    }

    /**
     * Adds additional code to the tracker
     *
     * @param string $code  The code to add
     * @param string $block The block where to add the code
     * @param bool $prepend Whether to prepend the code to the code block
     * @param Site|string|null $config Restrict the part to a specific site (can be either a string like site_1 or
     *                                 default or a Site instance). By default, it will be added to the current site.
     */
    public function addCodePart(string $code, string $block = null, bool $prepend = false, $config = null)
    {
        $configKey = $this->getConfigKey($config);

        $this->getCodeContainer()->addCodePart($configKey, $code, $block, $prepend);
    }

    abstract protected function getCodeContainer(): CodeContainer;
    abstract protected function generateCode(string $configKey = self::CONFIG_KEY_MAIN_DOMAIN, Site $site = null);

    /**
     * Get config key from an input which can either be a string key or a Site. If nothing is given
     * the current site will be resolved.
     *
     * @param Site|string|null $config
     *
     * @return string
     */
    private function getConfigKey($config = null): string
    {
        $configKey = null;
        if (null !== $config) {
            if ($config instanceof Site) {
                $configKey = $this->getSiteConfigKey($config);
            } else {
                $configKey = (string)$config;
            }
        } else {
            $configKey = self::CONFIG_KEY_MAIN_DOMAIN;
            if ($this->siteResolver->isSiteRequest()) {
                $configKey = $this->getSiteConfigKey($this->siteResolver->getSite());
            }
        }

        return $configKey;
    }

    private function getSiteConfigKey(Site $site): string
    {
        return sprintf('site_%s', $site->getId());
    }
}
