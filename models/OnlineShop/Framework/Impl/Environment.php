<?php

class OnlineShop_Framework_Impl_Environment implements OnlineShop_Framework_IEnvironment {
    const SESSION_NAMESPACE = "onlineshop";
    const SESSION_KEY_CUSTOM_ITEMS = "customitems";
    const SESSION_KEY_USERID = "userid";
    const SESSION_KEY_TENANT = "currenttenant";
    const SESSION_KEY_SUB_TENANT = "currentsubtenant";

    /**
     * @var Zend_Session_Namespace
     */
    protected $session;

    protected $customItems = array();
    protected $userId = -1;
    protected $currentTenant = null;
    protected $currentSubTenant = null;

    public function __construct() {
        $this->loadFromSession();
    }

    protected function loadFromSession() {
        if(php_sapi_name() != "cli") {
            $this->session = new Zend_Session_Namespace(self::SESSION_NAMESPACE);

            $key = self::SESSION_KEY_CUSTOM_ITEMS;
            $this->customItems = $this->session->$key;
            if ($this->customItems==null){
                $this->customItems=array();
            }

            $key = self::SESSION_KEY_USERID;
            $this->userId = $this->session->$key;

            $key = self::SESSION_KEY_TENANT;
            $this->currentTenant = $this->session->$key;

            $key = self::SESSION_KEY_SUB_TENANT;
            $this->currentSubTenant = $this->session->$key;
        }
    }

    public function save() {
        $key = self::SESSION_KEY_CUSTOM_ITEMS;
        $this->session->$key = $this->customItems;

        $key = self::SESSION_KEY_USERID;
        $this->session->$key = $this->userId;

        $key = self::SESSION_KEY_TENANT;
        $this->session->$key = $this->currentTenant;

        $key = self::SESSION_KEY_SUB_TENANT;
        $this->session->$key = $this->currentSubTenant;
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


    public function clearEnvironment() {
        $key = self::SESSION_KEY_CUSTOM_ITEMS;
        unset($this->session->$key);
        $this->customItems = null;

        $key = self::SESSION_KEY_USERID;
        unset($this->session->$key);
        $this->userId = null;

        $key = self::SESSION_KEY_TENANT;
        unset($this->session->$key);
        $this->currentTenant = null;

        $key = self::SESSION_KEY_SUB_TENANT;
        unset($this->session->$key);
        $this->currentSubTenant = null;
    }

    public function setCurrentTenant($currentTenant) {
        $this->currentTenant = $currentTenant;
    }

    /**
     * @return string
     */
    public function getCurrentTenant() {
        return $this->currentTenant;
    }

    public function setCurrentSubTenant($currentSubTenant) {
        $this->currentSubTenant = $currentSubTenant;
    }


    /**
     * @return string
     */
    public function getCurrentSubTenant() {
        return $this->currentSubTenant;
    }



}

