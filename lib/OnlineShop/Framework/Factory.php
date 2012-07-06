<?php

class OnlineShop_Framework_Factory {
    const CONFIG_PATH = "/OnlineShop/conf/OnlineShopConfig.xml";

    /**
     * @var OnlineShop_Framework_Factory
     */
    private static $instance;
    private $config;

    /**
     * @var OnlineShop_Framework_ICartManager
     */
    private $cartManager;
    private $priceSystems;
    private $availabilitySystems;

    private $checkoutManagers;

    /**
     * @var OnlineShop_Framework_IEnvironment
     */
    private $environment;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new OnlineShop_Framework_Factory();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->initEnvironment();
    }

    public function getConfig() {
        return $this->config;
    }

    private function initEnvironment() {
        $configPath = OnlineShop_Plugin::getConfig(true)->onlineshop_config_file;
        $config = new Zend_Config_Xml(PIMCORE_DOCUMENT_ROOT . $configPath);

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
        $configPath = OnlineShop_Plugin::getConfig(true)->onlineshop_config_file;
        $this->config = new Zend_Config_Xml(PIMCORE_DOCUMENT_ROOT . $configPath);
        $this->checkConfig($this->config);
    }

    private function checkConfig($config) {
        $this->configureCartManager($config);
        $this->configurePriceSystem($config);
        $this->configureAvailabilitySystem($config);

        $this->configureCheckoutManager($config);

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
                $priceSystem = new $class();
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

    public function getFilterService(Zend_View $view) {
        return new OnlineShop_Framework_FilterService($this->config->onlineshop->filtertypes, $view);
    }



    public function saveState() {
        $this->cartManager->save();
        $this->environment->save();
    }


}