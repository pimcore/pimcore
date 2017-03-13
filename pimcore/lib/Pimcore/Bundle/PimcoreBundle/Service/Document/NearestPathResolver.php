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

namespace Pimcore\Bundle\PimcoreBundle\Service\Document;

use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;

class NearestPathResolver
{
    /**
     * @var Document[]
     */
    protected $cache;

    /**
     * @var Document\Service|Document\Service\Dao
     */
    protected $documentService;

    /**
     * @param Document\Service $documentService
     */
    public function __construct(Document\Service $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Get the nearest document by path. Used to match nearest document for a static route.
     *
     * @param string|Request $path
     * @param bool $ignoreHardlinks
     * @param array $types
     *
     * @return Document|Document\PageSnippet|null
     */
    public function getNearestDocumentByPath($path, $ignoreHardlinks = false, $types = [])
    {
        if ($path instanceof Request) {
            $path = urldecode($path->getPathInfo());
        }

        $cacheKey = $ignoreHardlinks . implode("-", $types);
        $document = null;

        if (isset($this->cache[$cacheKey])) {
            $document = $this->cache[$cacheKey];
        } else {
            $paths    = ['/'];
            $tmpPaths = [];

            $pathParts = explode("/", $path);
            foreach ($pathParts as $pathPart) {
                $tmpPaths[] = $pathPart;

                $t = implode("/", $tmpPaths);
                if (!empty($t)) {
                    $paths[] = $t;
                }
            }

            $paths = array_reverse($paths);
            foreach ($paths as $p) {
                if ($document = Document::getByPath($p)) {
                    if (empty($types) || in_array($document->getType(), $types)) {
                        $document = $this->cache[$cacheKey] = $document;
                        break;
                    }
                } elseif (Site::isSiteRequest()) {
                    // also check for a pretty url in a site
                    $site = Site::getCurrentSite();

                    // undo the changed made by the site detection in self::match()
                    $originalPath = preg_replace("@^" . $site->getRootPath() . "@", "", $p);

                    $sitePrettyDocId = $this->documentService->getDocumentIdByPrettyUrlInSite($site, $originalPath);
                    if ($sitePrettyDocId) {
                        if ($sitePrettyDoc = Document::getById($sitePrettyDocId)) {
                            $document = $this->cache[$cacheKey] = $sitePrettyDoc;
                            break;
                        }
                    }
                }
            }
        }

        if ($document) {
            if (!$ignoreHardlinks) {
                if ($document instanceof Document\Hardlink) {
                    if ($hardLinkedDocument = Document\Hardlink\Service::getNearestChildByPath($document, $path)) {
                        $document = $hardLinkedDocument;
                    } else {
                        $document = Document\Hardlink\Service::wrap($document);
                    }
                }
            }

            return $document;
        }

        return null;
    }
}
