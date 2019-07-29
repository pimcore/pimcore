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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

interface CategoryPageViewInterface
{
    /**
     * Tracks a category page view
     *
     * @param array|string $category One or more categories matching the page
     * @param mixed $page            Any kind of page information you can use to track your page
     */
    public function trackCategoryPageView($category, $page = null);
}

class_alias(CategoryPageViewInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICategoryPageView');
