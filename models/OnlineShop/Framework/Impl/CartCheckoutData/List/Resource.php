<?php

class OnlineShop_Framework_Impl_CartCheckoutData_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * @return array
     */
    public function load() {
        $items = array();

        $cartCheckoutDataItems = $this->db->fetchAll("SELECT cartid, `key` FROM " . OnlineShop_Framework_Impl_CartCheckoutData_Resource::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartCheckoutDataItems as $item) {
            $items[] = OnlineShop_Framework_Impl_CartCheckoutData::getByKeyCartId($item['key'], $item['cartid']);
        }
        $this->model->setCartCheckoutDataItems($items);

        return $items;
    }

    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . OnlineShop_Framework_Impl_CartCheckoutData_Resource::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amount"];
    }

}