<?php

class OnlineShop_Framework_Impl_Environment implements OnlineShop_Framework_IEnvironment {
    const SESSION_NAMESPACE = "onlineshop";
    const SESSION_KEY_CUSTOM_ITEMS = "customitems";
    const SESSION_KEY_USERID = "userid";

    /**
     * @var Zend_Session_Namespace
     */
    protected $session;

    protected $customItems = array();
    protected $userId = -1;

    public function __construct() {
        $this->loadFromSession();
    }

    protected function loadFromSession() {
        $this->session = new Zend_Session_Namespace(self::SESSION_NAMESPACE);

        $key = self::SESSION_KEY_CUSTOM_ITEMS;
        $this->customItems = $this->session->$key;
        if ($this->customItems==null){
            $this->customItems=array();
        }

        $key = self::SESSION_KEY_USERID;
        $this->userId = $this->session->$key;
    }

    public function save() {
        $key = self::SESSION_KEY_CUSTOM_ITEMS;
        $this->session->$key = $this->customItems;

        $key = self::SESSION_KEY_USERID;
        $this->session->$key = $this->userId;
    }

    public function getAllCustomItems() {
        return $this->customItems;
    }

    public function getCustomItem($key) {
        return $this->customItems[$key];
    }

    public function setCustomItem($key, $value) {
        $this->customItems[$key] = $value;
    }

    /**
     * @return int
     */
    public function getCurrentUserId() {
        if(empty($this->userId)) {
            return -1; 
        }
        return $this->userId;
    }

    /**
     * @param int $user
     * @return void
     */
    public function setCurrentUserId($userId) {
        $this->userId = $userId;
    }

    public function removeCustomItem($key) {
        unset($this->customItems[$key]);
    }
}
