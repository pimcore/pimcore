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
use Pimcore\Model\Site;
use Pimcore\Sitemap\Document\FilterInterface;
use Pimcore\Sitemap\Element\FilterInterface as ElementFilterInterface;

/**
 * Decorates a standard element filter and makes it available as filter for the DocumentTreeGenerator
 */
class ElementFilterDecorator implements FilterInterface
{
    /**
     * @var ElementFilterInterface
     */
    private $filter;

    public function __construct(ElementFilterInterface $filter)
    {
        $this->filter = $filter;
    }

    public function canBeAdded(Document $document, Site $site = null): bool
    {
        return $this->filter->canBeAdded($document);
    }

    public function handlesChildren(Document $document, Site $site = null): bool
    {
        return $this->filter->handlesChildren($document);
    }
}
