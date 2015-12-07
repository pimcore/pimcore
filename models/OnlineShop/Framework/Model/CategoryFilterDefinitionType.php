<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\Model;

/**
 * Abstract base class for filter definition type field collections for category filter
 */
abstract class CategoryFilterDefinitionType extends \OnlineShop\Framework\Model\AbstractFilterDefinitionType {

    /**
     * @return string
     */
    public function getField() {
        if($this->getIncludeParentCategories()) {
            return "parentCategoryIds";
        } else {
            return "categoryIds";
        }
    }

    /**
     * @return bool
     */
    public function getIncludeParentCategories() {
        return false;
    }

}