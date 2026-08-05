<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\SeoBundle\Sitemap\Document\Filter;

use Pimcore\Bundle\SeoBundle\Sitemap\Document\DocumentGeneratorContext;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\FilterInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Site;

/**
 * Filters document if it is a site root, but doesn't match the current site. This used to exclude
 * sites from the default section.
 */
class SiteRootFilter implements FilterInterface
{
    private ?array $siteRoots = null;

    public function canBeAdded(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        if (!$element instanceof Document) {
            return false;
        }

        $site = null;
        if ($context instanceof DocumentGeneratorContext && $context->hasSite()) {
            $site = $context->getSite();
        }

        if ($this->isExcludedSiteRoot($element, $site)) {
            return false;
        }

        return true;
    }

    public function handlesChildren(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        return $this->canBeAdded($element, $context);
    }

    private function isExcludedSiteRoot(Document $document, ?Site $site = null): bool
    {
        if (null === $this->siteRoots) {
            $sites = (new Site\Listing())->load();

            $this->siteRoots = array_map(function (Site $site) {
                return $site->getRootId();
            }, $sites);
        }

        if (!in_array($document->getId(), $this->siteRoots, true)) {
            return false;
        }

        // no site, but document is a site root -> exclude
        if (null === $site) {
            return true;
        }

        // exclude site root if it is not the root of the current site
        return $document->getId() !== $site->getRootId();
    }
}
