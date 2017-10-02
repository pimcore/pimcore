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

namespace Pimcore\Navigation;

use Pimcore\Cache as CacheManager;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Navigation\Page\Document as DocumentPage;

class Builder
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var string
     */
    protected $htmlMenuIdPrefix;

    /**
     * @var string
     */
    protected $pageClass = DocumentPage::class;

    /**
     * @param RequestHelper $requestHelper
     * @param string|null $pageClass
     */
    public function __construct(RequestHelper $requestHelper, string $pageClass = null)
    {
        $this->requestHelper = $requestHelper;

        if (null !== $pageClass) {
            $this->pageClass = $pageClass;
        }
    }

    /**
     * @param Document $activeDocument
     * @param null $navigationRootDocument
     * @param null $htmlMenuIdPrefix
     * @param null $pageCallback
     * @param bool|string $cache
     *
     * @return mixed|\Pimcore\Navigation\Container
     *
     * @throws \Exception
     */
    public function getNavigation($activeDocument, $navigationRootDocument = null, $htmlMenuIdPrefix = null, $pageCallback = null, $cache = true)
    {
        $cacheEnabled = $cache !== false;

        $this->htmlMenuIdPrefix = $htmlMenuIdPrefix;

        if (!$navigationRootDocument) {
            $navigationRootDocument = Document::getById(1);
        }

        $cacheKeys = ['root_id__' . $navigationRootDocument->getId(), $htmlMenuIdPrefix];

        if (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();
            $cacheKeys[] = 'site__' . $site->getId();
        }

        if (is_string($cache)) {
            $cacheKeys[] = 'custom__' . $cache;
        }

        if ($pageCallback instanceof \Closure) {
            $cacheKeys[] = 'pageCallback_' . closureHash($pageCallback);
        }

        $cacheKey = 'nav_' . md5(serialize($cacheKeys));
        $navigation = CacheManager::load($cacheKey);

        if (!$navigation || !$cacheEnabled) {
            $navigation = new \Pimcore\Navigation\Container();

            if ($navigationRootDocument->hasChildren()) {
                $rootPage = $this->buildNextLevel($navigationRootDocument, true, $pageCallback);
                $navigation->addPages($rootPage);
            }

            // we need to force caching here, otherwise the active classes and other settings will be set and later
            // also written into cache (pass-by-reference) ... when serializing the data directly here, we don't have this problem
            if ($cacheEnabled) {
                CacheManager::save($navigation, $cacheKey, ['output', 'navigation'], null, 999, true);
            }
        }

        // set active path
        $request = $this->requestHelper->getMasterRequest();

        // try to find a page matching exactly the request uri
        $activePages = $navigation->findAllBy('uri', $request->getRequestUri());

        if (empty($activePages)) {
            // try to find a page matching the path info
            $activePages = $navigation->findAllBy('uri', $request->getPathInfo());
        }

        if (empty($activePages)) {
            // use the provided pimcore document
            $activePages = $navigation->findAllBy('realFullPath', $activeDocument->getRealFullPath());
        }

        if (empty($activePages)) {
            // find by link target
            $activePages = $navigation->findAllBy('uri', $activeDocument->getFullPath());
        }

        // cleanup active pages from links
        // pages have priority, if we don't find any active page, we use all we found
        $tmpPages = [];
        foreach ($activePages as $page) {
            if ($page instanceof DocumentPage && $page->getDocumentType() != 'link') {
                $tmpPages[] = $page;
            }
        }
        if (count($tmpPages)) {
            $activePages = $tmpPages;
        }

        if (!empty($activePages)) {
            // we found an active document, so we can build the active trail by getting respectively the parent
            foreach ($activePages as $activePage) {
                $this->addActiveCssClasses($activePage, true);
            }
        } else {
            // we don't have an active document, so we try to build the trail on our own
            $allPages = $navigation->findAllBy('uri', '/.*/', true);

            /** @var Page|Page\Document $page */
            foreach ($allPages as $page) {
                $activeTrail = false;

                if ($page->getUri() && strpos($activeDocument->getRealFullPath(), $page->getUri() . '/') === 0) {
                    $activeTrail = true;
                }

                if ($page instanceof DocumentPage) {
                    if ($page->getDocumentType() == 'link') {
                        if ($page->getUri() && strpos($activeDocument->getFullPath(), $page->getUri() . '/') === 0) {
                            $activeTrail = true;
                        }
                    }
                }

                if ($activeTrail) {
                    $page->setActive(true);
                    $page->setClass($page->getClass() . ' active active-trail');
                }
            }
        }

        return $navigation;
    }

    /**
     * @param Page $page
     * @param bool $isActive
     */
    protected function addActiveCssClasses(Page $page, $isActive = false)
    {
        $page->setActive(true);

        $parent = $page->getParent();
        $isRoot = false;
        $classes = '';

        if ($parent instanceof DocumentPage) {
            $this->addActiveCssClasses($parent);
        } else {
            $isRoot = true;
        }

        $classes .= ' active';

        if (!$isActive) {
            $classes .= ' active-trail';
        }

        if ($isRoot && $isActive) {
            $classes .= ' mainactive';
        }

        $page->setClass($page->getClass() . $classes);
    }

    /**
     * @param $pageClass
     *
     * @return $this
     */
    public function setPageClass(string $pageClass)
    {
        $this->pageClass = $pageClass;

        return $this;
    }

    /**
     * Returns the name of the pageclass
     *
     * @return String
     */
    public function getPageClass()
    {
        return $this->pageClass;
    }

    /**
     * @param Document $parentDocument
     *
     * @return Document[]
     */
    protected function getChilds(Document $parentDocument)
    {
        return $parentDocument->getChildren();
    }

    /**
     * @param $parentDocument
     * @param bool $isRoot
     * @param callable $pageCallback
     *
     * @return array
     */
    protected function buildNextLevel($parentDocument, $isRoot = false, $pageCallback = null)
    {
        $pages = [];
        $childs = $this->getChilds($parentDocument);

        if (!is_array($childs)) {
            return $pages;
        }

        foreach ($childs as $child) {
            $classes = '';

            if ($child instanceof Document\Hardlink) {
                $child = Document\Hardlink\Service::wrap($child);
            }

            if (($child instanceof Document\Page or $child instanceof Document\Link) and $child->getProperty('navigation_name')) {
                $path = $child->getFullPath();
                if ($child instanceof Document\Link) {
                    $path = $child->getHref();
                }

                /** @var DocumentPage $page */
                $page = new $this->pageClass();
                $page->setUri($path . $child->getProperty('navigation_parameters') . $child->getProperty('navigation_anchor'));
                $page->setLabel($child->getProperty('navigation_name'));
                $page->setActive(false);
                $page->setId($this->htmlMenuIdPrefix . $child->getId());
                $page->setClass($child->getProperty('navigation_class'));
                $page->setTarget($child->getProperty('navigation_target'));
                $page->setTitle($child->getProperty('navigation_title'));
                $page->setAccesskey($child->getProperty('navigation_accesskey'));
                $page->setTabindex($child->getProperty('navigation_tabindex'));
                $page->setRelation($child->getProperty('navigation_relation'));
                $page->setDocument($child);

                if ($child->getProperty('navigation_exclude') || !$child->getPublished()) {
                    $page->setVisible(false);
                }

                if ($isRoot) {
                    $classes .= ' main';
                }

                $page->setClass($page->getClass() . $classes);

                if ($child->hasChildren()) {
                    $childPages = $this->buildNextLevel($child, false, $pageCallback);
                    $page->setPages($childPages);
                }

                if ($pageCallback instanceof \Closure) {
                    $pageCallback($page, $child);
                }

                $pages[] = $page;
            }
        }

        return $pages;
    }
}
