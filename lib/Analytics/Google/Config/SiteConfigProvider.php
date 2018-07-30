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

namespace Pimcore\Analytics\Google\Config;

use Pimcore\Analytics\SiteId\SiteId;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Pimcore\Model\Site;

class SiteConfigProvider
{
    /**
     * @var SiteIdProvider
     */
    private $siteIdProvider;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        SiteIdProvider $siteIdProvider,
        ConfigProvider $configProvider
    ) {
        $this->siteIdProvider = $siteIdProvider;
        $this->configProvider = $configProvider;
    }

    public function getSiteConfig(Site $site = null)
    {
        $siteId = $this->getSiteId($site);
        $config = $this->configProvider->getConfig();

        return $config->getConfigForSite($siteId->getConfigKey());
    }

    public function isSiteReportingConfigured(Site $site = null): bool
    {
        $siteId = $this->getSiteId($site);
        $config = $this->configProvider->getConfig();

        return $config->isReportingConfigured($siteId->getConfigKey());
    }

    private function getSiteId(Site $site = null): SiteId
    {
        $siteId = null;
        if (null === $site) {
            $siteId = $this->siteIdProvider->getForRequest();
        } else {
            $siteId = SiteId::forSite($site);
        }

        return $siteId;
    }
}
