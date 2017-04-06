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

namespace Pimcore\Service\Request;

use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;

class SiteResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_SITE = '_site';
    const ATTRIBUTE_SITE_PATH = '_site_path';

    /**
     * @param Request $request
     * @param Site $site
     */
    public function setSite(Request $request, Site $site)
    {
        $request->attributes->set(static::ATTRIBUTE_SITE, $site);
    }

    /**
     * @param Request|null $request
     *
     * @return Site|null
     */
    public function getSite(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        return $request->attributes->get(static::ATTRIBUTE_SITE);
    }

    /**
     * @param Request $request
     * @param string $path
     */
    public function setSitePath(Request $request, $path)
    {
        $request->attributes->set(static::ATTRIBUTE_SITE_PATH, $path);
    }

    /**
     * @param Request|null $request
     *
     * @return string|null
     */
    public function getSitePath(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        return $request->attributes->get(static::ATTRIBUTE_SITE_PATH);
    }

    /**
     * @param Request|null $request
     *
     * @return bool
     */
    public function isSiteRequest(Request $request = null)
    {
        $site = $this->getSite($request);

        return null !== $site && $site instanceof Site;
    }
}
