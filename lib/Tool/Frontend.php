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

namespace Pimcore\Tool;

use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Document;
use Pimcore\Model\Site;

final class Frontend
{
    private const SITE_CACHE_KEY = 'sites_full_list';

    public static function isDocumentInSite(?Site $site, Document $document): bool
    {
        $inSite = true;

        if ($site && $site->getRootDocument() instanceof Document\Page) {
            if (strpos($document->getRealFullPath(), $site->getRootDocument()->getRealFullPath() . '/') !== 0) {
                $inSite = false;
            }
        }

        return $inSite;
    }

    public static function isDocumentInCurrentSite(Document $document): bool
    {
        if (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();
            if ($site instanceof Site) {
                return self::isDocumentInSite($site, $document);
            }
        }

        return true;
    }

    public static function getSiteForDocument(Document $document): ?Site
    {
        $siteIdOfDocument = self::getSiteIdForDocument($document);

        if(!$siteIdOfDocument) {
            return null;
        }

        if (RuntimeCache::isRegistered(SELF::SITE_CACHE_KEY)) {
            $sites = RuntimeCache::get(SELF::SITE_CACHE_KEY);
        } else {
            $sites = self::getFullSiteListing();
        }

        foreach ($sites as $site) {
            if($site->getId() == $siteIdOfDocument) {
                return $site;
            }
        }

        return null;
    }

    public static function getSiteIdForDocument(Document $document): ?int
    {
        $pathSiteMappingCacheKey = 'path_site_mapping';

        $pathMapping = Pimcore\Cache::load($pathSiteMappingCacheKey);

        if (!$pathMapping) {
            $pathMapping = [];
        }

        if (array_key_exists($document->getRealFullPath(), $pathMapping)) {
            return $pathMapping[$document->getRealFullPath()];
        }

        foreach (self::getFullSiteListing() as $site) {
            /** @var Site $site */
            if (strpos($document->getRealFullPath(), $site->getRootPath() . '/') === 0 || $site->getRootDocument()->getId() == $document->getId()) {
                $pathMapping[$document->getRealFullPath()] = $site->getId();
                Pimcore\Cache::save($pathMapping, $pathSiteMappingCacheKey, ['system', 'resource'], null, 997);
                return $site->getId();
            }
        }

        return null;
    }

    /**
     * @return Site\Listing[]
     */
    private static function getFullSiteListing() : array
    {
        if (RuntimeCache::isRegistered(SELF::SITE_CACHE_KEY)) {
            $sites = RuntimeCache::get(SELF::SITE_CACHE_KEY);
        } else {
            $sites = new Site\Listing();
            $sites->setOrderKey('(SELECT LENGTH(`path`) FROM documents WHERE documents.id = sites.rootId) DESC', false);
            $sites = $sites->load();
            RuntimeCache::set(SELF::SITE_CACHE_KEY, $sites);
        }
        return $sites;
    }

    public static function isOutputCacheEnabled(): bool|array
    {
        $cacheService = Pimcore::getContainer()->get(FullPageCacheListener::class);

        if ($cacheService->isEnabled()) {
            return [
                'enabled' => true,
                'lifetime' => $cacheService->getLifetime(),
            ];
        }

        return false;
    }
}
