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

class PublishedFilter implements FilterInterface
{
    public function canBeAdded(AbstractElement $element, GeneratorContextInterface $context): bool
    {
        if (method_exists($element, 'isPublished')) {
            return (bool)$element->isPublished();
        }

        return true;
    }

    public function handlesChildren(AbstractElement $element, GeneratorContextInterface $context): bool
    {
        return $this->canBeAdded($element, $context);
    }
}
