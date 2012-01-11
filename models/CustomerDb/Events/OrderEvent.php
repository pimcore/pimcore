<?php
 
class CustomerDb_Events_OrderEvent extends CustomerDb_Events_Abstract {
    protected $definitionFile = "/plugins/OnlineShop/models/CustomerDb/Events/orderEvent.xml";
    public $orderObject;
    public $icon = "myicon";

    /**
     * @var string
     */
    public $eventtype = "OrderEvent";

    public function getTableName() {
        return "plugin_customerdb_event_orderEvent";
    }


    public function allowManualCreation() {
        return false;
    }

    public function isEditable() {
        return false;
    }

    public function isRemoveable() {
        return false;
    }

    public function setOrderObject($orderObject) {
        $this->orderObject = $orderObject;
    }

    public function getOrderObject() {
        return $this->orderObject;
    }
}
