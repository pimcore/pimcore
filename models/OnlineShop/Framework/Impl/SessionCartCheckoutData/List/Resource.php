<?php

class OnlineShop_Framework_Impl_SessionCartCheckoutData_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * @return array
     */
    public function load() {
        $allCheckoutData = new Zend_Session_Namespace('checkoutData');
        $conditions = explode('||', str_replace(' WHERE ', '', $this->getCondition()));
        $checkoutDataItems = array();
        $items = array();
        foreach ($allCheckoutData as $checkoutDataItem) {
            $hit = null;
            foreach ($conditions as $condition){
                $expandedCondition = explode('=', $condition);
                $value = trim($expandedCondition[1]);
                $key = trim((string) $expandedCondition[0]);
                if ($checkoutDataItem->$key == $value AND ($hit === null OR $hit === true)) {
                    $hit = true;
                } else {
                    break;
                }
            }
            if ($hit === true) {
                $checkoutDataItems[] = $checkoutDataItem;
            }
        }
        foreach ($checkoutDataItems as $item) {
            $items[] = OnlineShop_Framework_Impl_SessionCartCheckoutData::getByKeyCartId($item->key, $item->cartId);
        }
        $this->model->setCartCheckoutDataItems($items);
        return $items;
    }

    public function getTotalCount() {
        $allCheckoutData = new Zend_Session_Namespace('checkoutData');
        $conditions = explode('||', str_replace(' WHERE ', '', $this->getCondition()));
        $checkoutDataItems = array();
        $items = array();
        foreach ($allCheckoutData as $checkoutDataItem) {
            $hit = null;
            foreach ($conditions as $condition){
                $expandedCondition = explode('=', $condition);
                $value = trim($expandedCondition[1]);
                $key = (string) $expandedCondition[0];
                if ($checkoutDataItem->$key == $value AND ($hit === null OR $hit === true)) {
                    $hit = true;
                } else {
                    break;
                }
            }
            if ($hit === true) {
                $checkoutDataItems[] = $checkoutDataItem;
            }
        }
        foreach ($checkoutDataItems as $item) {
            $items[] = OnlineShop_Framework_Impl_SessionCartCheckoutData::getByKeyCartId($item->key, $item->cartId);
        }

        return count($items);
    }

}