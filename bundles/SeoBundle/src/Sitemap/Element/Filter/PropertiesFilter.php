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

namespace Pimcore\Bundle\SeoBundle\Sitemap\Element\Filter;

use Pimcore\Bundle\SeoBundle\Sitemap\Element\FilterInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Model\Element\ElementInterface;

/**
 * Filters element based on the sitemaps_exclude and sitemaps_exclude_children properties.
 */
class PropertiesFilter implements FilterInterface
{
    const PROPERTY_EXCLUDE = 'sitemaps_exclude';

    const PROPERTY_EXCLUDE_CHILDREN = 'sitemaps_exclude_children';

    public function canBeAdded(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        if ($this->getBoolProperty($element, self::PROPERTY_EXCLUDE)) {
            return false;
        }

        return true;
    }

    public function handlesChildren(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        if ($this->getBoolProperty($element, self::PROPERTY_EXCLUDE_CHILDREN)) {
            return false;
        }

        return true;
    }

    private function getBoolProperty(ElementInterface $document, string $property): bool
    {
        if (!$document->hasProperty($property)) {
            return false;
        }

        return (bool)$document->getProperty($property);
    }
}
