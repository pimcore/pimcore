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

namespace OnlineShop\Framework\PricingManager\Condition;

class CatalogCategory implements ICategory
{
    /**
     * @var \OnlineShop\Framework\Model\AbstractCategory[]
     */
    protected $categories = array();

    /**
     * @param \OnlineShop\Framework\Model\AbstractCategory[] $categories
     *
     * @return ICategory
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @return \OnlineShop\Framework\Model\AbstractCategory[]
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
        $json = array(
            'type' => 'CatalogCategory',
            'categories' => array()
        );

        // add categories
        foreach($this->getCategories() as $category)
        {
            /* @var \OnlineShop\Framework\Model\AbstractCategory $category */
            $json['categories'][] = array(
                $category->getId(),
                $category->getFullPath()
            );
        }

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return \OnlineShop\Framework\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $categories = array();
        foreach($json->categories as $cat)
        {
            $category = $this->loadCategory($cat->id);
            if($category)
            {
                $categories[] = $category;
            }
        }
        $this->setCategories( $categories );

        return $this;
    }

    /**
     * don't cache the entire category object
     * @return array
     */
    public function __sleep()
    {
        foreach($this->categories as $key => $cat)
        {
            /* @var \OnlineShop\Framework\Model\AbstractCategory $cat */
            $this->categories[ $key ] = $cat->getId();
        }

        return array('categories');
    }

    /**
     * restore category
     */
    public function __wakeup()
    {
        foreach($this->categories as $key => $cat_id)
        {
            $category = $this->loadCategory($cat_id);
            if($category)
            {
                $this->categories[ $key ] = $category;
            }
        }
    }

    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        foreach($environment->getCategories() as $category)
        {
            /* @var \OnlineShop\Framework\Model\AbstractCategory $category */
            foreach($this->getCategories() as $allow)
            {
                /* @var \OnlineShop\Framework\Model\AbstractCategory $allow */
                if(strpos($category->getFullPath(), $allow->getFullPath()) !== false) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * @param $id
     *
     * @return \Pimcore\Model\Object\Concrete|null
     */
    protected function loadCategory($id)
    {
        return \Pimcore\Model\Object\Concrete::getById($id);
    }
}