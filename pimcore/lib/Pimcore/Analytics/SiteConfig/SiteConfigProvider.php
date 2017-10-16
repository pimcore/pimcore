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

namespace Pimcore\Analytics\SiteConfig;

use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;

class SiteConfigProvider
{
    /**
     * @var SiteResolver
     */
    private $siteResolver;

    public function __construct(SiteResolver $siteResolver)
    {
        $this->siteResolver = $siteResolver;
    }

    public function getSiteConfig(Request $request = null): SiteConfig
    {
        if ($this->siteResolver->isSiteRequest($request)) {
            $site = $this->siteResolver->getSite();

            return SiteConfig::forSite($site);
        }

        return SiteConfig::forMainDomain();
    }

    /**
     * @param bool $includeMainDomain
     *
     * @return SiteConfig[]
     */
    public function getSiteConfigs(bool $includeMainDomain = true): array
    {
        /** @var Site\Listing|Site\Listing\Dao $sites */
        $sites = new Site\Listing();

        $configs = [];

        if ($includeMainDomain) {
            $configs[] = SiteConfig::forMainDomain();
        }

        foreach ($sites->load() as $site) {
            $configs[] = SiteConfig::forSite($site);

        }

        return $configs;
    }
}
