<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 12.04.13
 * Time: 08:54
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_Condition_CatalogCategory implements OnlineShop_Framework_Pricing_Condition_ICategory
{
    /**
     * @var OnlineShop_Framework_AbstractCategory[]
     */
    protected $categories = array();

    /**
     * @param OnlineShop_Framework_AbstractCategory[] $categories
     *
     * @return OnlineShop_Framework_Pricing_Condition_ICategory
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @return OnlineShop_Framework_AbstractCategory[]
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
            /* @var OnlineShop_Framework_AbstractCategory $category */
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
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $categories = array();
        foreach($json->categories as $cat)
        {
            $categories[] = OnlineShop_Framework_AbstractCategory::getById($cat->id);
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
            /* @var OnlineShop_Framework_AbstractCategory $cat */
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
            $this->categories[ $key ] = OnlineShop_Framework_AbstractCategory::getById($cat_id);
        }
    }

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        foreach($environment->getCategories() as $category)
        {
            /* @var OnlineShop_Framework_AbstractCategory $category */
            foreach($this->getCategories() as $allow)
            {
                /* @var OnlineShop_Framework_AbstractCategory $allow */
                if(strpos($category->getFullPath(), $allow->getFullPath()) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}