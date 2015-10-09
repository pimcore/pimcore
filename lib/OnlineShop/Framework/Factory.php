<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


use OnlineShop\Framework\IOrderManager;

class OnlineShop_Framework_Factory {

    /**
     * framework configuration file
     */
    const CONFIG_PATH = "/OnlineShop/conf/OnlineShopConfig.xml";

    /**
     * @var OnlineShop_Framework_Factory
     */
    private static $instance;

    /**
     * @var Zend_Config_Xml
     */
    private $config;

    /**
     * @var OnlineShop_Framework_ICartManager
     */
    private $cartManager;

    /**
     * @var OnlineShop_Framework_IPriceSystem
     */
    private $priceSystems;

    /**
     * @var OnlineShop_Framework_IAvailabilitySystem
     */
    private $availabilitySystems;

    /**
     * @var OnlineShop_Framework_ICheckoutManager
     */
    private $checkoutManagers;

    /**
     * @var OnlineShop_Framework_IPricingManager
     */
    private $pricingManager;

    /**
     * @var IOrderManager
     */
    private $orderManager;

    /**
     * @var OnlineShop_OfferTool_IService
     */
    private $offerToolService;

    /**
     * @var string[]
     */
    private $allTenants;

    /**
     * @var OnlineShop_Framework_IEnvironment
     */
    private $environment;

    /**
     * @var OnlineShop_Framework_IPaymentManager
     */
    private $paymentManager;


    /**
     * @var OnlineShop_Framework_IVoucherService
     */
    private $voucherService;

    /**
     * @var OnlineShop_Framework_VoucherService_ITokenManager[]
     */
    private $tokenManagers = array();


    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new OnlineShop_Framework_Factory();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * creates new factory instance and optionally resets environment too
     *
     * @param bool|true $keepEnvironment
     * @return OnlineShop_Framework_Factory
     */
    public static function resetInstance($keepEnvironment = true) {
        if($keepEnvironment) {
            $environment = self::$instance->getEnvironment();
        } else {
            $environment = null;
        }

        self::$instance = new OnlineShop_Framework_Factory($environment);
        self::$instance->init();
        return self::$instance;
    }

    private function __construct($environment = null) {
        $this->environment = $environment;
    }

    public function getConfig() {
        if(empty($this->config)) {
            if(!$config = \Pimcore\Model\Cache::load("onlineshop_config")) {
                $configPath = OnlineShop_Plugin::getConfig(true)->onlineshop_config_file;
                $config = new Zend_Config_Xml(PIMCORE_DOCUMENT_ROOT . $configPath, null, true);
                \Pimcore\Model\Cache::save($config, "onlineshop_config", array("ecommerceconfig"), 9999);
            }
            $this->config = $config;
        }

        return $this->config;
    }

    private function initEnvironment() {

        $config = $this->getConfig();

        //Environment
        if (empty($config->onlineshop->environment->class)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No Environment class defined.");
        } else {
            if (class_exists($config->onlineshop->environment->class)) {
                $this->environment = new $config->onlineshop->environment->class($config->onlineshop->environment->config);
                if (!($this->environment instanceof OnlineShop_Framework_IEnvironment)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Environment class " . $config->onlineshop->environment->class . " does not implement OnlineShop_Framework_IEnvironment.");
                }
            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Environment class " . $config->onlineshop->environment->class . " not found.");
            }
        }
    }

    private function init() {
        $config = $this->getConfig();
        $this->checkConfig($config);
    }

    private function checkConfig($config) {
        $this->configureCartManager($config);
        $this->configurePriceSystem($config);
        $this->configureAvailabilitySystem($config);

        $this->configureCheckoutManager($config);
        $this->configurePricingManager($config);
        $this->configurePaymentManager($config);
        $this->configureOrderManager($config);

        $this->configureOfferToolService($config);
    }

    private function configureCartManager($config) {
        if (empty($config->onlineshop->cartmanager->class)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No Cartmanager class defined.");
        } else {
            if (class_exists($config->onlineshop->cartmanager->class)) {
                $this->cartManager = new $config->onlineshop->cartmanager->class($config->onlineshop->cartmanager->config);
                if (!($this->cartManager instanceof OnlineShop_Framework_ICartManager)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Cartmanager class " . $config->onlineshop->cartmanager->class . " does not implement OnlineShop_Framework_ICartManager.");
                }
            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Cartmanager class " . $config->onlineshop->cartmanager->class . " not found.");
            }
        }
    }

    private function configurePriceSystem($config) {
        if (empty($config->onlineshop->pricesystems)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No Pricesystems defined.");
        }
        //$this->priceSystems=array();
        $priceSystemConfigs = $config->onlineshop->pricesystems->pricesystem;
        if($priceSystemConfigs->class) {
            $priceSystemConfigs = array($priceSystemConfigs);
        }

        $this->priceSystems = new stdClass();
        if(!empty($priceSystemConfigs)) {
            foreach ($priceSystemConfigs as $priceSystemConfig) {
                if (empty($priceSystemConfig->class)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("No Pricesystem class defined.");
                }
                if (empty($priceSystemConfig->name)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("No Pricesystem name defined.");
                }
                $name = $priceSystemConfig->name;
                if (!empty($this->priceSystems->$name)){
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("More than one Pricesystem ".$name . " is defined!");
                }
                /* if (!class_exists($priceSystemConfig->class)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . "  not found.");
                }*/
                $class = $priceSystemConfig->class;
                $priceSystem = new $class($priceSystemConfig->config);
                if (!$priceSystem instanceof OnlineShop_Framework_IPriceSystem){
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . " does not implement OnlineShop_Framework_IPriceSystem.");
                }
                $this->priceSystems->$name=$priceSystem;
            }

        }

    }
    private function configureAvailabilitySystem($config) {

        if (empty($config->onlineshop->availablitysystems)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No AvailabilitySystem defined.");
        }
        //$this->priceSystems=array();
        $availabilitySystemConfigs = $config->onlineshop->availablitysystems->availablitysystem;
        if($availabilitySystemConfigs->class) {
            $availabilitySystemConfigs = array($availabilitySystemConfigs);
        }

        $this->availabilitySystems = new stdClass();
        if(!empty($availabilitySystemConfigs)) {
            foreach ($availabilitySystemConfigs as $availabilitySystemConfig) {
                if (empty($availabilitySystemConfig->class)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("No AvailabilitySystem class defined.");
                }
                if (empty($availabilitySystemConfig->name)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("No AvailabilitySystem name defined.");
                }
                $name = $availabilitySystemConfig->name;
                if (!empty($this->availablitysystems->$name)){
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("More than one AvailabilitySystem ".$name . " is defined!");
                }
                /* if (!class_exists($priceSystemConfig->class)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . "  not found.");
                }*/

                $class = $availabilitySystemConfig->class;
                $availabilitySystem = new $class();
                if (! $availabilitySystem instanceof OnlineShop_Framework_IAvailabilitySystem){
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("AvailabilitySystem class " . $availabilitySystemConfig->class . " does not implement OnlineShop_Framework_IPriceSystem.");
                }
                $this->availabilitySystems->$name= $availabilitySystem;
            }

        }

    }


    private function configureCheckoutManager($config) {
        if (empty($config->onlineshop->checkoutmanager->class)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No Checkoutmanager class defined.");
        } else {
            if (!class_exists($config->onlineshop->checkoutmanager->class)) {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Checkoutmanager class " . $config->onlineshop->checkoutmanager->class . " not found.");
            }
        }
    }

    /**
     * @param Zend_Config_Xml $config
     *
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    private function configurePricingManager(Zend_Config_Xml $config) {
        if (empty($config->onlineshop->pricingmanager->class)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No PricingManager class defined.");
        } else {
            if (class_exists($config->onlineshop->pricingmanager->class)) {
                $this->pricingManager = new $config->onlineshop->pricingmanager->class($config->onlineshop->pricingmanager->config);
                if (!($this->pricingManager instanceof OnlineShop_Framework_IPricingManager)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("PricingManager class " . $config->onlineshop->pricingmanager->class . " does not implement OnlineShop_Framework_IPricingManager.");
                }
            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("PricingManager class " . $config->onlineshop->pricingmanager->class . " not found.");
            }
        }
    }


    private function configureOfferToolService($config) {
        if(!empty($config->onlineshop->offertool->class)) {
            if (!class_exists($config->onlineshop->offertool->class)) {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("OfferTool class " . $config->onlineshop->offertool->class . " not found.");
            }
            if (!class_exists($config->onlineshop->offertool->orderstorage->offerClass)) {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("OfferToolOffer class " . $config->onlineshop->offertool->orderstorage->offerClass . " not found.");
            }
            if (!class_exists($config->onlineshop->offertool->orderstorage->offerItemClass)) {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("OfferToolOfferItem class " . $config->onlineshop->offertool->orderstorage->offerItemClass . " not found.");
            }
        }
    }


    /**
     * @param Zend_Config $config
     *
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    private function configurePaymentManager(Zend_Config $config)
    {
        if (!empty($config->onlineshop->paymentmanager->class))
        {
            if (class_exists($config->onlineshop->paymentmanager->class))
            {
                $this->paymentManager = new $config->onlineshop->paymentmanager->class($config->onlineshop->paymentmanager->config);
                if (!($this->paymentManager instanceof OnlineShop_Framework_IPaymentManager))
                {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("PaymentManager class " . $config->onlineshop->paymentmanager->class . " does not implement OnlineShop_Framework_IPaymentManager.");
                }
            }
            else
            {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("PaymentManager class " . $config->onlineshop->paymentmanager->class . " not found.");
            }
        }
    }


    /**
     * @param Zend_Config $config
     *
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    private function configureOrderManager(Zend_Config $config)
    {
        if (!empty($config->onlineshop->ordermanager->class))
        {
            if (class_exists($config->onlineshop->ordermanager->class))
            {
                $this->orderManager = new $config->onlineshop->ordermanager->class( $config->onlineshop->ordermanager->config );
                if (!($this->orderManager instanceof OnlineShop\Framework\IOrderManager))
                {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("OrderManager class " . $config->onlineshop->ordermanager->class . " does not implement OnlineShop\\Framework\\IOrderManager.");
                }
            }
            else
            {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("OrderManager class " . $config->onlineshop->ordermanager->class . " not found.");
            }
        }
    }


    public function getCartManager() {
        return $this->cartManager;
    }

    /**
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     * @param OnlineShop_Framework_ICart $cart
     * @param string $name optional name of checkout manager, in case there are more than one configured
     * @return OnlineShop_Framework_ICheckoutManager
     */
    public function getCheckoutManager(OnlineShop_Framework_ICart $cart, $name = null) {

        if(empty($this->checkoutManagers[$cart->getId()])) {
            if($name) {
                $managerConfigName = "checkoutmanager_" . $name;
                $manager = new $this->config->onlineshop->$managerConfigName->class($cart, $this->config->onlineshop->$managerConfigName->config);
                if (!($manager instanceof OnlineShop_Framework_ICheckoutManager)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Checkoutmanager class " . $this->config->onlineshop->$managerConfigName->class . " does not implement OnlineShop_Framework_ICheckoutManager.");
                }
            } else {
                $manager = new $this->config->onlineshop->checkoutmanager->class($cart, $this->config->onlineshop->checkoutmanager->config);
                if (!($manager instanceof OnlineShop_Framework_ICheckoutManager)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Checkoutmanager class " . $this->config->onlineshop->checkoutmanager->class . " does not implement OnlineShop_Framework_ICheckoutManager.");
                }
            }

            $this->checkoutManagers[$cart->getId()] = $manager;
        }

        return $this->checkoutManagers[$cart->getId()];
    }

    /**
     * @return OnlineShop_Framework_IEnvironment
     */
    public function getEnvironment() {
        if(!$this->environment) {
            $this->initEnvironment();
        }
        return $this->environment;
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param null $name
     * @return OnlineShop_Framework_IPriceSystem
     */
    public function getPriceSystem($name = null) {
        if ($name == null) {
            $name = "default";
        }

        if ($ps = $this->priceSystems->$name) {
            return $ps;
        }
        else {
            throw new OnlineShop_Framework_Exception_UnsupportedException("priceSystem " . $name . " is not supported, check configuration!");
        }

    }
    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param null $name
     * @return OnlineShop_Framework_IAvailabilitySystem
     */
    public function getAvailabilitySystem($name = null) {
        if ($name == null) {
            $name = "default";
        }

        if ($ps = $this->availabilitySystems->$name) {
            return $ps;
        }
        else {
            throw new OnlineShop_Framework_Exception_UnsupportedException("availabilitySystem " . $name . " is not supported, check configuration!");
        }

    }


    /**
     * @var OnlineShop_Framework_IndexService
     */
    private $indexService = null;

    /**
     * @return OnlineShop_Framework_IndexService
     */
    public function getIndexService() {
        if(empty($this->indexService)) {
            $this->indexService = new OnlineShop_Framework_IndexService($this->config->onlineshop->productindex);
        }
        return $this->indexService;
    }


    /**
     * @return string[]
     */
    public function getAllTenants() {
        if(empty($this->allTenants) && $this->config->onlineshop->productindex->tenants && $this->config->onlineshop->productindex->tenants instanceof Zend_Config) {
            foreach($this->config->onlineshop->productindex->tenants as $name => $tenant) {
                $this->allTenants[$name] = $name;
            }
        }
        return $this->allTenants;
    }


    /**
     * @param Zend_View $view
     *
     * @return OnlineShop_Framework_FilterService
     */
    public function getFilterService(Zend_View $view) {

        $filterTypes = $this->getIndexService()->getCurrentTenantConfig()->getFilterTypeConfig();
        if(!$filterTypes)
        {
            $filterTypes = $this->config->onlineshop->filtertypes;
        }

        return new OnlineShop_Framework_FilterService($filterTypes, $view);
    }



    public function saveState() {
        $this->cartManager->save();
        $this->environment->save();
    }


    /**
     * @return OnlineShop_Framework_IPricingManager
     */
    public function getPricingManager()
    {
        return $this->pricingManager;
    }

    /**
     * @return OnlineShop_OfferTool_IService
     */
    public function getOfferToolService() {
        if(empty($this->offerToolService)) {
            $className = (string)$this->config->onlineshop->offertool->class;
            $this->offerToolService = new $className(
                (string) $this->config->onlineshop->offertool->orderstorage->offerClass,
                (string) $this->config->onlineshop->offertool->orderstorage->offerItemClass,
                (string) $this->config->onlineshop->offertool->orderstorage->parentFolderPath
            );
        }

        return $this->offerToolService;
    }


    /**
     * @return OnlineShop_Framework_IPaymentManager
     */
    public function getPaymentManager()
    {
        return $this->paymentManager;
    }


    /**
     * @return IOrderManager
     */
    public function getOrderManager()
    {
        return $this->orderManager;
    }


    /**
     * @return OnlineShop_Framework_IVoucherService
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function getVoucherService() {

        if(empty($this->voucherService)) {
            $this->voucherService = new $this->config->onlineshop->voucherservice->class($this->config->onlineshop->voucherservice->config);
            if (!($this->voucherService instanceof OnlineShop_Framework_IVoucherService)) {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Voucher Service class " . $this->config->onlineshop->voucherservice->class . " does not implement OnlineShop_Framework_IVoucherService.");
            }

        }

        return $this->voucherService;
    }


    /**
     * @param OnlineShop_Framework_AbstractVoucherTokenType $configuration
     * @return OnlineShop_Framework_VoucherService_ITokenManager
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function getTokenManager(OnlineShop_Framework_AbstractVoucherTokenType $configuration) {
        $id   = $configuration->getObject()->getId();
        $type = $configuration->getType();

        if(empty($this->tokenManagers[$id])) {

            $tokenManagerClass = $this->config->onlineshop->voucherservice->tokenmanagers->$type;

            if($tokenManagerClass) {
                $tokenManager = new $tokenManagerClass->class($configuration);
                if (!($tokenManager instanceof OnlineShop_Framework_VoucherService_ITokenManager)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Token Manager class " . $tokenManagerClass->class . " does not implement OnlineShop_Framework_VoucherService_ITokenManager.");
                }

                $this->tokenManagers[$id] = $tokenManager;

            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Token Manager for " . $type . " not defined.");
            }

        }
        return $this->tokenManagers[$id];

    }

}
