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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment;

class CatalogCategory extends AbstractObjectListCondition implements ICategory
{
    /**
     * @var AbstractCategory[]
     */
    protected $categories = [];

    /**
     * Serialized category IDs
     *
     * @var array
     */
    protected $categoryIds = [];

    /**
     * @param AbstractCategory[] $categories
     *
     * @return ICategory
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return AbstractCategory[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'CatalogCategory',
            'categories' => []
        ];

        // add categories
        foreach ($this->getCategories() as $category) {
            /* @var AbstractCategory $category */
            $json['categories'][] = [
                $category->getId(),
                $category->getFullPath()
            ];
        }

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $categories = [];
        foreach ($json->categories as $cat) {
            $category = $this->loadObject($cat->id);
            if ($category) {
                $categories[] = $category;
            }
        }
        $this->setCategories($categories);

        return $this;
    }

    /**
     * Don't cache the entire category object
     *
     * @return array
     */
    public function __sleep()
    {
        return $this->handleSleep('categories', 'categoryIds');
    }

    /**
     * Restore categories from serialized ID list
     */
    public function __wakeup()
    {
        $this->handleWakeup('categories', 'categoryIds');
    }

    /**
     * @param IEnvironment $environment
     *
     * @return bool
     */
    public function check(IEnvironment $environment)
    {
        foreach ($environment->getCategories() as $category) {
            /* @var AbstractCategory $category */
            foreach ($this->getCategories() as $allow) {
                /* @var AbstractCategory $allow */
                if (strpos($category->getFullPath(), $allow->getFullPath()) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
