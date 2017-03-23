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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Condition;

class CatalogCategory extends AbstractObjectListCondition implements ICategory
{
    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory[]
     */
    protected $categories = array();

    /**
     * Serialized category IDs
     *
     * @var array
     */
    protected $categoryIds = array();

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory[] $categories
     *
     * @return ICategory
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory[]
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
            /* @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory $category */
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
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $categories = array();
        foreach($json->categories as $cat)
        {
            $category = $this->loadObject($cat->id);
            if($category)
            {
                $categories[] = $category;
            }
        }
        $this->setCategories( $categories );

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
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
    {
        foreach($environment->getCategories() as $category)
        {
            /* @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory $category */
            foreach($this->getCategories() as $allow)
            {
                /* @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory $allow */
                if(strpos($category->getFullPath(), $allow->getFullPath()) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
