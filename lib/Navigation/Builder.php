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

namespace Pimcore\Navigation;

use CallbackFilterIterator;
use Closure;
use Exception;
use Pimcore\Cache as CacheManager;
use Pimcore\Http\RequestHelper;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Navigation\Iterator\PrefixRecursiveFilterIterator;
use Pimcore\Navigation\Page\Document as DocumentPage;
use Pimcore\Navigation\Page\Url;
use RecursiveIteratorIterator;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Builder
{
    private RequestHelper $requestHelper;

    /**
     * @internal
     */
    protected ?string $htmlMenuIdPrefix = null;

    /**
     * @internal
     */
    protected string $pageClass = DocumentPage::class;

    private int $currentLevel = 0;

    private array $navCacheTags = [];

    private OptionsResolver $optionsResolver;

    public function __construct(RequestHelper $requestHelper, ?string $pageClass = null)
    {
        $this->requestHelper = $requestHelper;

        if (null !== $pageClass) {
            $this->pageClass = $pageClass;
        }

        $this->optionsResolver = new OptionsResolver();
        $this->configureOptions($this->optionsResolver);
    }

    protected function configureOptions(OptionsResolver $options): void
    {
        $options->setDefaults([
            'root' => null,
            'htmlMenuPrefix' => null,
            'pageCallback' => null,
            'rootCallback' => null,
            'cache' => true,
            'cacheLifetime' => null,
            'maxDepth' => null,
            'active' => null,
            'markActiveTrail' => true,
        ]);

        $options->setAllowedTypes('root', [Document::class, 'null']);
        $options->setAllowedTypes('htmlMenuPrefix', ['string', 'null']);
        $options->setAllowedTypes('pageCallback', [Closure::class, 'null']);
        $options->setAllowedTypes('rootCallback', [Closure::class, 'null']);
        $options->setAllowedTypes('cache', ['string', 'bool']);
        $options->setAllowedTypes('cacheLifetime', ['int', 'null']);
        $options->setAllowedTypes('maxDepth', ['int', 'null']);
        $options->setAllowedTypes('active', [Document::class, 'null']);
        $options->setAllowedTypes('markActiveTrail', ['bool']);
    }

    protected function resolveOptions(array $options): array
    {
        return $this->optionsResolver->resolve($options);
    }

    /**
     * @param array{
     *     root?: ?Document,
     *     htmlMenuPrefix?: ?string,
     *     pageCallback?: ?\Closure,
     *     rootCallback?: ?\Closure,
     *     cache?: string|bool,
     *     cacheLifetime?: ?int,
     *     maxDepth?: ?int,
     *     active?: ?Document,
     *     markActiveTrail?: bool
     * } $params
     *
     * @throws Exception
     */
    public function getNavigation(array $params): Container
    {
        [
            'root' => $navigationRootDocument,
            'htmlMenuPrefix' => $htmlMenuIdPrefix,
            'pageCallback' => $pageCallback,
            'rootCallback' => $rootCallback,
            'cache' => $cache,
            'cacheLifetime' => $cacheLifetime,
            'maxDepth' => $maxDepth,
            'active' => $activeDocument,
            'markActiveTrail' => $markActiveTrail,
        ] = $this->resolveOptions($params);

        $cacheEnabled = $cache !== false;

        $this->htmlMenuIdPrefix = $htmlMenuIdPrefix;

        if (!$navigationRootDocument) {
            $navigationRootDocument = Document::getById(1);
        }

        $navigation = null;
        $cacheKey = null;
        if ($cacheEnabled) {
            // the cache key consists out of the ID and the class name (eg. for hardlinks) of the root document and the optional html prefix
            $cacheKeys = ['root_id__' . $navigationRootDocument->getId(), $htmlMenuIdPrefix, get_class($navigationRootDocument)];

            if (Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
                $cacheKeys[] = 'site__' . $site->getId();
            }

            if (is_string($cache)) {
                $cacheKeys[] = 'custom__' . $cache;
            }

            if ($pageCallback instanceof Closure) {
                $cacheKeys[] = 'pageCallback_' . closureHash($pageCallback);
            }

            if ($maxDepth) {
                $cacheKeys[] = 'maxDepth_' . $maxDepth;
            }

            $cacheKey = 'nav_' . md5(serialize($cacheKeys));
            $navigation = CacheManager::load($cacheKey);
        }
        if (!$navigation instanceof Container) {
            $navigation = new Container();

            $this->navCacheTags = ['output', 'navigation'];

            if ($navigationRootDocument->hasChildren()) {
                $this->currentLevel = 0;
                $rootPage = $this->buildNextLevel($navigationRootDocument, true, $pageCallback, [], $maxDepth);
                $navigation->addPages($rootPage);
            }

            if ($rootCallback instanceof Closure) {
                $rootCallback($navigation);
            }

            // we need to force caching here, otherwise the active classes and other settings will be set and later
            // also written into cache (pass-by-reference) ... when serializing the data directly here, we don't have this problem
            if ($cacheEnabled) {
                CacheManager::save($navigation, $cacheKey, $this->navCacheTags, $cacheLifetime, 999, true);
            }
        }

        if ($markActiveTrail) {
            $this->markActiveTrail($navigation, $activeDocument);
        }

        return $navigation;
    }

    /**
     * @internal
     */
    protected function markActiveTrail(Container $navigation, ?Document $activeDocument): void
    {
        $activePages = [];

        if ($this->requestHelper->hasMainRequest()) {
            $request = $this->requestHelper->getMainRequest();

            // try to find a page matching exactly the request uri
            $activePages = $this->findActivePages($navigation, 'uri', $request->getRequestUri());

            if (empty($activePages)) {
                // try to find a page matching the path info
                $activePages = $this->findActivePages($navigation, 'uri', $request->getPathInfo());
            }
        }

        if ($activeDocument) {
            if (empty($activePages)) {
                // use the provided pimcore document
                $activePages = $this->findActivePages($navigation, 'realFullPath', $activeDocument->getRealFullPath());
            }

            if (empty($activePages)) {
                // find by link target
                $activePages = $this->findActivePages($navigation, 'uri', $activeDocument->getFullPath());
            }
        }

        $isLink = static fn ($page): bool => $page instanceof DocumentPage && $page->getDocumentType() === 'link';

        // cleanup active pages from links
        // pages have priority, if we don't find any active page, we use all we found
        if ($nonLinkPages = array_filter($activePages, static fn ($page): bool => !$isLink($page))) {
            $activePages = $nonLinkPages;
        }

        if ($activePages) {
            // we found an active document, so we can build the active trail by getting respectively the parent
            foreach ($activePages as $activePage) {
                $this->addActiveCssClasses($activePage, true);
            }

            return;
        }

        if ($activeDocument) {
            // we didn't find the active document, so we try to build the trail on our own
            $allPages = new RecursiveIteratorIterator($navigation, RecursiveIteratorIterator::SELF_FIRST);

            foreach ($allPages as $page) {
                if (!$page instanceof Url || !$page->getUri()) {
                    continue;
                }

                $uri = $page->getUri() . '/';
                $isActive = str_starts_with($activeDocument->getRealFullPath(), $uri)
                    || ($isLink($page) && str_starts_with($activeDocument->getFullPath(), $uri));

                if ($isActive) {
                    $page->setActive(true);
                    $page->setClass($page->getClass() . ' active active-trail');
                }
            }
        }
    }

    /**
     * @internal
     *
     * @param Container $navigation navigation container to iterate
     * @param string $property name of property to match against
     * @param string $value value to match property against
     *
     * @return Page[]
     */
    protected function findActivePages(Container $navigation, string $property, string $value): array
    {
        $filterByPrefix = new PrefixRecursiveFilterIterator($navigation, $property, $value);
        $flatten = new RecursiveIteratorIterator($filterByPrefix, RecursiveIteratorIterator::SELF_FIRST);
        $filterMatches = new CallbackFilterIterator($flatten, static fn (Page $page): bool => $page->get($property) === $value);

        return iterator_to_array($filterMatches, false);
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    protected function addActiveCssClasses(Page $page, bool $isActive = false): void
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
     * @return $this
     */
    public function setPageClass(string $pageClass): static
    {
        $this->pageClass = $pageClass;

        return $this;
    }

    /**
     * Returns the name of the pageclass
     */
    public function getPageClass(): string
    {
        return $this->pageClass;
    }

    /**
     * @return Document[]
     */
    protected function getChildren(Document $parentDocument): array
    {
        // the intention of this function is mainly to be overridden in order to customize the behavior of the navigation
        // e.g. for custom filtering and other very specific use-cases
        if ($parentDocument instanceof Document\Hardlink || $parentDocument instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            return $parentDocument->getChildren()->getData();
        }

        return $parentDocument->getChildren()->load();
    }

    /**
     * @return Page[]
     *
     * @throws Exception
     *
     * @internal
     */
    protected function buildNextLevel(Document $parentDocument, bool $isRoot = false, callable $pageCallback = null, array $parents = [], int $maxDepth = null): array
    {
        $this->currentLevel++;
        $pages = [];
        $children = $this->getChildren($parentDocument);
        $parents[$parentDocument->getId()] = $parentDocument;

        foreach ($children as $child) {
            $classes = '';

            if ($child instanceof Document\Hardlink) {
                $child = Document\Hardlink\Service::wrap($child);
                if (!$child) {
                    continue;
                }
            }

            // infinite loop detection, we use array keys here, because key lookups are much faster
            if (isset($parents[$child->getId()])) {
                Logger::critical('Navigation: Document with ID ' . $child->getId() . ' would produce an infinite loop -> skipped, parent IDs (' . implode(',', array_keys($parents)) . ')');

                continue;
            }

            if ($child instanceof Document\Folder || $child instanceof Document\Page || $child instanceof Document\Link) {
                $path = $child->getFullPath();
                if ($child instanceof Document\Link) {
                    $path = $child->getHref();
                }

                /** @var DocumentPage $page */
                $page = new $this->pageClass();
                if (!$child instanceof Document\Folder) {
                    $page->setUri($path . $child->getProperty('navigation_parameters') . $child->getProperty('navigation_anchor'));
                }
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

                if (trim((string)$child->getProperty('navigation_name')) === '' || $child->getProperty('navigation_exclude') || !$child->getPublished()) {
                    $page->setVisible(false);
                }

                if ($isRoot) {
                    $classes .= ' main';
                }

                $page->setClass($page->getClass() . $classes);

                if ($child->hasChildren() && (!$maxDepth || $maxDepth > $this->currentLevel)) {
                    $childPages = $this->buildNextLevel($child, false, $pageCallback, $parents, $maxDepth);
                    $page->setPages($childPages);
                }

                if ($pageCallback instanceof Closure) {
                    $pageCallback($page, $child);
                }

                $this->navCacheTags[] = $page->getDocument()->getCacheTag();

                $pages[] = $page;
            }
        }

        $this->currentLevel--;

        return $pages;
    }
}
