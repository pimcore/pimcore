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

namespace Pimcore\Sitemap\Element\Filter;

use Pimcore\Model\Element\AbstractElement;
use Pimcore\Sitemap\Element\FilterInterface;
use Pimcore\Sitemap\Element\GeneratorContextInterface;

/**
 * Filters element based on the sitemaps_exclude and sitemaps_exclude_children properties.
 */
class PropertiesFilter implements FilterInterface
{
    const PROPERTY_EXCLUDE = 'sitemaps_exclude';
    const PROPERTY_EXCLUDE_CHILDREN = 'sitemaps_exclude_children';

    public function canBeAdded(AbstractElement $element, GeneratorContextInterface $context): bool
    {
        if ($this->getBoolProperty($element, self::PROPERTY_EXCLUDE)) {
            return false;
        }

        return true;
    }

    public function handlesChildren(AbstractElement $element, GeneratorContextInterface $context): bool
    {
        if ($this->getBoolProperty($element, self::PROPERTY_EXCLUDE_CHILDREN)) {
            return false;
        }

        return true;
    }

    private function getBoolProperty(AbstractElement $document, string $property): bool
    {
        if (!$document->hasProperty($property)) {
            return false;
        }

        return (bool)$document->getProperty($property);
    }
}
