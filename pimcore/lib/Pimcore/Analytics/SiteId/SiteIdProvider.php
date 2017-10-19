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

namespace Pimcore\Analytics\SiteId;

use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;

class SiteIdProvider
{
    /**
     * @var SiteResolver
     */
    private $siteResolver;

    public function __construct(SiteResolver $siteResolver)
    {
        $this->siteResolver = $siteResolver;
    }

    /**
     * Resolve the site identifier for the given request
     *
     * @param Request|null $request
     *
     * @return SiteId
     */
    public function getForRequest(Request $request = null): SiteId
    {
        if ($this->siteResolver->isSiteRequest($request)) {
            $site = $this->siteResolver->getSite();

            return SiteId::forSite($site);
        }

        return SiteId::forMainDomain();
    }

    /**
     * Get a site id for a config key
     *
     * @param string $configKey
     *
     * @return SiteId
     */
    public function getSiteId(string $configKey): SiteId
    {
        foreach ($this->getSiteIds() as $siteId) {
            if ($siteId->getConfigKey() === $configKey) {
                return $siteId;
            }
        }

        throw new \InvalidArgumentException(sprintf('Site config for key "%s" was not found'));
    }

    /**
     * Get all available site ids
     *
     * @param bool $includeMainDomain
     *
     * @return SiteId[]
     */
    public function getSiteIds(bool $includeMainDomain = true): array
    {
        /** @var Site\Listing|Site\Listing\Dao $sites */
        $sites = new Site\Listing();

        $ids = [];

        if ($includeMainDomain) {
            $ids[] = SiteId::forMainDomain();
        }

        foreach ($sites->load() as $site) {
            $ids[] = SiteId::forSite($site);

        }

        return $ids;
    }
}
