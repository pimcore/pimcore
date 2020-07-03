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

namespace Pimcore\Sitemap\Document\Filter;

use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Site;
use Pimcore\Sitemap\Document\DocumentGeneratorContext;
use Pimcore\Sitemap\Element\FilterInterface;
use Pimcore\Sitemap\Element\GeneratorContextInterface;

/**
 * Filters document if it is a site root, but doesn't match the current site. This used to exclude
 * sites from the default section.
 */
class SiteRootFilter implements FilterInterface
{
    /**
     * @var array
     */
    private $siteRoots;

    public function canBeAdded(AbstractElement $element, GeneratorContextInterface $context): bool
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

    public function handlesChildren(AbstractElement $element, GeneratorContextInterface $context): bool
    {
        return $this->canBeAdded($element, $context);
    }

    private function isExcludedSiteRoot(Document $document, Site $site = null): bool
    {
        if (null === $this->siteRoots) {
            /** @var Site[] $sites */
            $sites = (new Site\Listing())->load();

            $this->siteRoots = array_map(function (Site $site) {
                return (int)$site->getRootId();
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
