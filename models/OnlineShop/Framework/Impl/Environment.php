<?php

class OnlineShop_Framework_Impl_Environment implements OnlineShop_Framework_IEnvironment {
    const SESSION_NAMESPACE = "onlineshop";
    const SESSION_KEY_CUSTOM_ITEMS = "customitems";
    const SESSION_KEY_USERID = "userid";
    const SESSION_KEY_USE_GUEST_CART = "useguestcart";
    const SESSION_KEY_ASSORTMENT_TENANT = "currentassortmenttenant";
    const SESSION_KEY_ASSORTMENT_SUB_TENANT = "currentassortmentsubtenant";
    const SESSION_KEY_CHECKOUT_TENANT = "currentcheckouttenant";
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


    protected $currentAssortmentTenant = null;
    protected $currentAssortmentSubTenant = null;
    protected $currentCheckoutTenant = null;

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

            $key = self::SESSION_KEY_ASSORTMENT_TENANT;
            $this->currentAssortmentTenant = $this->session->$key;

            $key = self::SESSION_KEY_ASSORTMENT_SUB_TENANT;
            $this->currentAssortmentSubTenant = $this->session->$key;

            $key = self::SESSION_KEY_CHECKOUT_TENANT;
            $this->currentCheckoutTenant = $this->session->$key;

            $key = self::SESSION_KEY_USE_GUEST_CART;
            $this->useGuestCart = $this->session->$key;
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

            $key = self::SESSION_KEY_ASSORTMENT_TENANT;
            $this->session->$key = $this->currentAssortmentTenant;

            $key = self::SESSION_KEY_ASSORTMENT_SUB_TENANT;
            $this->session->$key = $this->currentAssortmentSubTenant;

            $key = self::SESSION_KEY_CHECKOUT_TENANT;
            $this->session->$key = $this->currentCheckoutTenant;

            $key = self::SESSION_KEY_USE_GUEST_CART;
            $this->session->$key = $this->useGuestCart;
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

        $key = self::SESSION_KEY_ASSORTMENT_TENANT;
        unset($this->session->$key);
        $this->currentAssortmentTenant = null;

        $key = self::SESSION_KEY_ASSORTMENT_SUB_TENANT;
        unset($this->session->$key);
        $this->currentAssortmentSubTenant = null;

        $key = self::SESSION_KEY_CHECKOUT_TENANT;
        unset($this->session->$key);
        $this->currentCheckoutTenant = null;

        $key = self::SESSION_KEY_USE_GUEST_CART;
        unset($this->session->$key);
        $this->useGuestCart = false;
    }

    /**
     * @deprecated
     *
     * use setCurrentAssortmentTenant instead
     *
     * @param string $currentTenant
     * @return mixed|void
     */
    public function setCurrentTenant($currentTenant) {
        $this->setCurrentAssortmentTenant($currentTenant);
    }

    /**
     * @deprecated
     *
     * use getCurrentAssortmentTenant instead
     *
     * @return string
     */
    public function getCurrentTenant() {
        return $this->getCurrentAssortmentTenant();
    }

    /**
     * @deprecated
     *
     * use setCurrentAssortmentSubTenant instead
     *
     * @param string $currentSubTenant
     * @return mixed|void
     */
    public function setCurrentSubTenant($currentSubTenant) {
        $this->setCurrentAssortmentSubTenant($currentSubTenant);
    }


    /**
     * @deprecated
     *
     * use getCurrentAssortmentSubTenant instead
     *
     * @return string
     */
    public function getCurrentSubTenant() {
        return $this->getCurrentAssortmentSubTenant();
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

    /**
     * sets current assortment tenant which is used for indexing and product lists
     *
     * @param $tenant string
     * @return mixed
     */
    public function setCurrentAssortmentTenant($tenant)
    {
        $this->currentAssortmentTenant = $tenant;
    }

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentTenant()
    {
        return $this->currentAssortmentTenant;
    }

    /**
     * sets current assortment sub tenant which is used for indexing and product lists
     *
     * @param $subTenant string
     * @return mixed
     */
    public function setCurrentAssortmentSubTenant($subTenant)
    {
        $this->currentAssortmentSubTenant = $subTenant;
    }

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentSubTenant()
    {
        return $this->currentAssortmentSubTenant;
    }

    /**
     * sets current checkout tenant which is used for cart and checkout manager
     *
     * @param $tenant string
     * @return mixed
     */
    public function setCurrentCheckoutTenant($tenant)
    {
        if($this->currentCheckoutTenant != $tenant) {
            $this->currentCheckoutTenant = $tenant;
            OnlineShop_Framework_Factory::resetInstance();
        }
    }

    /**
     * gets current assortment tenant which is used for cart and checkout manager
     *
     * @return string
     */
    public function getCurrentCheckoutTenant()
    {
        return $this->currentCheckoutTenant;
    }
}
