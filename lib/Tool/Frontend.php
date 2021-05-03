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
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use Pimcore\Model\Document;
use Pimcore\Model\Site;

final class Frontend
{
    /**
     * @param Site|null $site
     * @param Document $document
     *
     * @return bool
     */
    public static function isDocumentInSite($site, $document)
    {
        $inSite = true;

        if ($site && $site->getRootDocument() instanceof Document\Page) {
            if (strpos($document->getRealFullPath(), $site->getRootDocument()->getRealFullPath() . '/') !== 0) {
                $inSite = false;
            }
        }

        return $inSite;
    }

    /**
     * @param Document $document
     *
     * @return bool
     */
    public static function isDocumentInCurrentSite($document)
    {
        if (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();
            if ($site instanceof Site) {
                return self::isDocumentInSite($site, $document);
            }
        }

        return true;
    }

    /**
     * @param Document $document
     *
     * @return Site|null
     */
    public static function getSiteForDocument($document)
    {
        $cacheKey = 'sites_full_list';
        if (\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            $sites = \Pimcore\Cache\Runtime::get($cacheKey);
        } else {
            $sites = new Site\Listing();
            $sites->setOrderKey('(SELECT LENGTH(path) FROM documents WHERE documents.id = sites.rootId) DESC', false);
            $sites = $sites->load();
            \Pimcore\Cache\Runtime::set($cacheKey, $sites);
        }

        foreach ($sites as $site) {
            if (strpos($document->getRealFullPath(), $site->getRootPath() . '/') === 0 || $site->getRootDocument()->getId() == $document->getId()) {
                return $site;
            }
        }

        return null;
    }

    /**
     * @return array|bool
     */
    public static function isOutputCacheEnabled()
    {
        $cacheService = \Pimcore::getContainer()->get(FullPageCacheListener::class);

        if ($cacheService->isEnabled()) {
            return [
                'enabled' => true,
                'lifetime' => $cacheService->getLifetime(),
            ];
        }

        return false;
    }
}
