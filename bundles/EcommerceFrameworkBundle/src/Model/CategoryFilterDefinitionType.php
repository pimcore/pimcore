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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

/**
 * Abstract base class for filter definition type field collections for category filter
 */
abstract class CategoryFilterDefinitionType extends AbstractFilterDefinitionType
{
    public function getField(): string
    {
        if ($this->getIncludeParentCategories()) {
            return 'parentCategoryIds';
        } else {
            return 'categoryIds';
        }
    }

    public function getIncludeParentCategories(): ?bool
    {
        return false;
    }
}
