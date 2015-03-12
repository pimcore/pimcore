<?php

class OnlineShop_Framework_Impl_Environment implements OnlineShop_Framework_IEnvironment {
    const SESSION_NAMESPACE = "onlineshop";
    const SESSION_KEY_CUSTOM_ITEMS = "customitems";
    const SESSION_KEY_USERID = "userid";
    const SESSION_KEY_TENANT = "currenttenant";
    const SESSION_KEY_SUB_TENANT = "currentsubtenant";
    const USER_ID_NOT_SET = -1;

    /**
     * @var Zend_Session_Namespace
     */
    protected $session;

    protected $customItems = array();

    /**
     * @var int
     */
    protected $userId = self::USER_ID_NOT_SET;

    /**
     * @var bool
     */
    protected $useGuestCart = false;


    protected $currentTenant = null;
    protected $currentSubTenant = null;

    public function __construct($config) {
        $this->loadFromSession();

        $locale = (string)$config->defaultlocale;
        if(empty($locale)) {
            $this->currencyLocale = Zend_Registry::get("Zend_Locale");
        } else {
            $this->currencyLocale = new Zend_Locale($locale);
        }
    }

    protected function loadFromSession() {
        // when $_SESSION[self::SESSION_NAMESPACE] is set, always load environment from session (also within cli scripts)
        if(php_sapi_name() != "cli" || $_SESSION[self::SESSION_NAMESPACE]) {
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
        // when $_SESSION[self::SESSION_NAMESPACE] is set, always save environment to session (also within cli scripts)
        if(php_sapi_name() != "cli" || $_SESSION[self::SESSION_NAMESPACE])
        {
            $key = self::SESSION_KEY_CUSTOM_ITEMS;
            $this->session->$key = $this->customItems;

            $key = self::SESSION_KEY_USERID;
            $this->session->$key = $this->userId;

            $key = self::SESSION_KEY_TENANT;
            $this->session->$key = $this->currentTenant;

            $key = self::SESSION_KEY_SUB_TENANT;
            $this->session->$key = $this->currentSubTenant;
        }
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
    public function getCurrentUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     *
     * @return $this
     */
    public function setCurrentUserId($userId)
    {
        $this->userId = (int)$userId;

        return $this;
    }


    /**
     * @return bool
     */
    public function hasCurrentUserId()
    {
        return $this->getCurrentUserId() !== self::USER_ID_NOT_SET;
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

    /**
     * @return null|Zend_Locale
     */
    public function getCurrencyLocale() {
        return $this->currencyLocale;
    }

    /**
     * @return boolean
     */
    public function getUseGuestCart()
    {
        return $this->useGuestCart;
    }

    /**
     * @param boolean $useGuestCart
     */
    public function setUseGuestCart($useGuestCart)
    {
        $this->useGuestCart = (bool)$useGuestCart;
    }
}
