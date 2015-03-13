<?php

class OnlineShop_Framework_Impl_CartItem_List_Resource extends \Pimcore\Model\Listing\Resource\AbstractResource {

    /**
     * @var string
     */
    protected $className = 'OnlineShop_Framework_Impl_CartItem';

    /**
     * @return array
     */
    public function load() {
        $items = array();
        $cartItems = $this->db->fetchAll("SELECT cartid, itemKey, parentItemKey FROM " . OnlineShop_Framework_Impl_CartItem_Resource::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartItems as $item) {
            $items[] = call_user_func(array($this->getClassName(), 'getByCartIdItemKey'), $item['cartid'], $item['itemKey'], $item['parentItemKey']);
        }
        $this->model->setCartItems($items);

        return $items;
    }

    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . OnlineShop_Framework_Impl_CartItem_Resource::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amount"];
    }


    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}