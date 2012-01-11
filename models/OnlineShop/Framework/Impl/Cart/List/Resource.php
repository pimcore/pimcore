<?php

class OnlineShop_Framework_Impl_Cart_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * @return array
     */
    public function load() {
        $carts = array();
        $cartIds = $this->db->fetchCol("SELECT id FROM " . OnlineShop_Framework_Impl_Cart_Resource::TABLE_NAME .
                                                 $this->getCondition() . $this->getOrder() . $this->getOffsetLimit());

        foreach ($cartIds as $id) {
            $carts[] = OnlineShop_Framework_Impl_Cart::getById($id);
        }

        $this->model->setCarts($carts);

        return $carts;
    }

    public function getTotalCount() {
        $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM `" . OnlineShop_Framework_Impl_Cart_Resource::TABLE_NAME . "`" . $this->getCondition());
        return $amount["amount"];
    }

}