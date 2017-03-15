<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle;

class Factory {

    /**
     * framework configuration file
     */
    const CONFIG_PATH = "/EcommerceFramework/conf/OnlineShopConfig.php";

    /**
     * @var Factory
     */
    private static $instance;

    /**
     * @var \Zend_Config
     */
    private $config;

    /**
     * @var \OnlineShop\Framework\CartManager\ICartManager
     */
    private $cartManager;

    /**
     * @var \OnlineShop\Framework\PriceSystem\IPriceSystem
     */
    private $priceSystems;

    /**
     * @var \OnlineShop\Framework\AvailabilitySystem\IAvailabilitySystem
     */
    private $availabilitySystems;

    /**
     * @var \OnlineShop\Framework\CheckoutManager\ICheckoutManager
     */
    private $checkoutManagers;

    /**
     * @var \OnlineShop\Framework\PricingManager\IPricingManager
     */
    private $pricingManager;

    /**
     * @var \OnlineShop\Framework\OrderManager\IOrderManager
     */
    private $orderManager;

    /**
     * @var \OnlineShop\Framework\OfferTool\IService
     */
    private $offerToolService;

    /**
     * @var string[]
     */
    private $allTenants;

    /**
     * @var \OnlineShop\Framework\IEnvironment
     */
    private $environment;

    /**
     * @var \OnlineShop\Framework\PaymentManager\IPaymentManager
     */
    private $paymentManager;


    /**
     * @var \OnlineShop\Framework\VoucherService\IVoucherService
     */
    private $voucherService;

    /**
     * @var \OnlineShop\Framework\VoucherService\TokenManager\ITokenManager[]
     */
    private $tokenManagers = array();

    /**
     * @var \OnlineShop\Framework\Tracking\TrackingManager
     */
    private $trackingManager;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Factory();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * creates new factory instance and optionally resets environment too
     *
     * @param bool|true $keepEnvironment
     * @return Factory
     */
    public static function resetInstance($keepEnvironment = true) {
        if($keepEnvironment) {
            $environment = self::$instance->getEnvironment();
        } else {
            $environment = null;
        }

        self::$instance = new Factory($environment);
        self::$instance->init();
        return self::$instance;
    }

    private function __construct($environment = null) {
        $this->environment = $environment;
    }

    public function getConfig() {
        if(empty($this->config)) {
//            $configPath = \OnlineShop\Plugin::getConfig(true)->onlineshop_config_file;

//            TODO
//            $this->config = new \Zend_Config(require PIMCORE_DOCUMENT_ROOT . $configPath, true);

            $this->config = new \Zend_Config(require PIMCORE_PROJECT_ROOT . '/legacy/website/var/plugins/EcommerceFramework/OnlineShopConfig.php', true);
        }

        return $this->config;
    }

    private function initEnvironment() {

        $config = $this->getConfig();

        //Environment
        if (empty($config->onlineshop->environment->class)) {
            throw new \OnlineShop\Framework\Exception\InvalidConfigException("No Environment class defined.");
        } else {
            if (class_exists($config->onlineshop->environment->class)) {
                $this->environment = new $config->onlineshop->environment->class($config->onlineshop->environment->config);
                if (!($this->environment instanceof \OnlineShop\Framework\IEnvironment)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("Environment class " . $config->onlineshop->environment->class . ' does not implement \OnlineShop\Framework\IEnvironment.');
                }
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Environment class " . $config->onlineshop->environment->class . " not found.");
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
        $this->configureTrackingManager($config);

        $this->configureOfferToolService($config);
    }

    private function configureCartManager($config) {
        if (empty($config->onlineshop->cartmanager->class)) {
            throw new \OnlineShop\Framework\Exception\InvalidConfigException("No Cartmanager class defined.");
        } else {
            if (class_exists($config->onlineshop->cartmanager->class)) {
                $this->cartManager = new $config->onlineshop->cartmanager->class($config->onlineshop->cartmanager->config);
                if (!($this->cartManager instanceof \OnlineShop\Framework\CartManager\ICartManager)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("Cartmanager class " . $config->onlineshop->cartmanager->class . " does not implement OnlineShop\Framework\CartManager\ICartManager.");
                }
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Cartmanager class " . $config->onlineshop->cartmanager->class . " not found.");
            }
        }
    }
    
    /**
     * Configure tracking manager
     *
     * @param \Zend_Config $config
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    private function configureTrackingManager(\Zend_Config $config)
    {
        if (!empty($config->onlineshop->trackingmanager->class)) {
            $trackingManagerClass = $config->onlineshop->trackingmanager->class;
            if (class_exists($trackingManagerClass)) {
                $instance = new $trackingManagerClass($config->onlineshop->trackingmanager->config);
                if ($instance instanceof \OnlineShop\Framework\Tracking\ITrackingManager) {
                    $this->trackingManager = $instance;
                } else {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException(sprintf('TrackingManager class %s does not implement OnlineShop\\Framework\\Tracking\\ITrackingManager', $trackingManagerClass));
                }
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException(sprintf('TrackingManager class %s not found.', $trackingManagerClass));
            }
        }
    }
    
    /**
     * Get tracking manager
     *
     * @return \OnlineShop\Framework\Tracking\TrackingManager
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function getTrackingManager()
    {
        if (null === $this->trackingManager) {
            throw new \OnlineShop\Framework\Exception\UnsupportedException('Tracking is not configured, check configuration!');
        }

        return $this->trackingManager;
    }

    private function configurePriceSystem($config) {
        if (empty($config->onlineshop->pricesystems)) {
            throw new \OnlineShop\Framework\Exception\InvalidConfigException("No Pricesystems defined.");
        }
        //$this->priceSystems=array();
        $priceSystemConfigs = $config->onlineshop->pricesystems->pricesystem;
        if($priceSystemConfigs->class) {
            $priceSystemConfigs = array($priceSystemConfigs);
        }

        $this->priceSystems = new \stdClass();
        if(!empty($priceSystemConfigs)) {
            foreach ($priceSystemConfigs as $priceSystemConfig) {
                if (empty($priceSystemConfig->class)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("No Pricesystem class defined.");
                }
                if (empty($priceSystemConfig->name)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("No Pricesystem name defined.");
                }
                $name = $priceSystemConfig->name;
                if (!empty($this->priceSystems->$name)){
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("More than one Pricesystem ".$name . " is defined!");
                }
                /* if (!class_exists($priceSystemConfig->class)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . "  not found.");
                }*/
                $class = $priceSystemConfig->class;
                $priceSystem = new $class($priceSystemConfig->config);
                if (!$priceSystem instanceof \OnlineShop\Framework\PriceSystem\IPriceSystem){
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . ' does not implement \OnlineShop\Framework\PriceSystem\IPriceSystem.');
                }
                $this->priceSystems->$name=$priceSystem;
            }

        }

    }
    private function configureAvailabilitySystem($config) {

        if (empty($config->onlineshop->availablitysystems)) {
            throw new \OnlineShop\Framework\Exception\InvalidConfigException("No AvailabilitySystem defined.");
        }
        //$this->priceSystems=array();
        $availabilitySystemConfigs = $config->onlineshop->availablitysystems->availablitysystem;
        if($availabilitySystemConfigs->class) {
            $availabilitySystemConfigs = array($availabilitySystemConfigs);
        }

        $this->availabilitySystems = new \stdClass();
        if(!empty($availabilitySystemConfigs)) {
            foreach ($availabilitySystemConfigs as $availabilitySystemConfig) {
                if (empty($availabilitySystemConfig->class)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("No AvailabilitySystem class defined.");
                }
                if (empty($availabilitySystemConfig->name)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("No AvailabilitySystem name defined.");
                }
                $name = $availabilitySystemConfig->name;
                if (!empty($this->availablitysystems->$name)){
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("More than one AvailabilitySystem ".$name . " is defined!");
                }
                /* if (!class_exists($priceSystemConfig->class)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . "  not found.");
                }*/

                $class = $availabilitySystemConfig->class;
                $availabilitySystem = new $class();
                if (! $availabilitySystem instanceof \OnlineShop\Framework\AvailabilitySystem\IAvailabilitySystem){
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("AvailabilitySystem class " . $availabilitySystemConfig->class . ' does not implement \OnlineShop\Framework\AvailabilitySystem\IAvailabilitySystem.');
                }
                $this->availabilitySystems->$name= $availabilitySystem;
            }

        }

    }


    private function configureCheckoutManager($config) {
        if (empty($config->onlineshop->checkoutmanager->class)) {
            throw new \OnlineShop\Framework\Exception\InvalidConfigException("No Checkoutmanager class defined.");
        } else {
            if (!class_exists($config->onlineshop->checkoutmanager->class)) {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Checkoutmanager class " . $config->onlineshop->checkoutmanager->class . " not found.");
            }
        }
    }

    /**
     * @param \Zend_Config $config
     *
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    private function configurePricingManager(\Zend_Config $config) {
        if (empty($config->onlineshop->pricingmanager->class)) {
            throw new \OnlineShop\Framework\Exception\InvalidConfigException("No PricingManager class defined.");
        } else {
            if (class_exists($config->onlineshop->pricingmanager->class)) {
                $this->pricingManager = new $config->onlineshop->pricingmanager->class($config->onlineshop->pricingmanager->config);
                if (!($this->pricingManager instanceof \OnlineShop\Framework\PricingManager\IPricingManager)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("PricingManager class " . $config->onlineshop->pricingmanager->class . ' does not implement \OnlineShop\Framework\PricingManager\IPricingManager.');
                }
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("PricingManager class " . $config->onlineshop->pricingmanager->class . " not found.");
            }
        }
    }


    private function configureOfferToolService($config) {
        if(!empty($config->onlineshop->offertool->class)) {
            if (!class_exists($config->onlineshop->offertool->class)) {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("OfferTool class " . $config->onlineshop->offertool->class . " not found.");
            }
            if (!class_exists($config->onlineshop->offertool->orderstorage->offerClass)) {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("OfferToolOffer class " . $config->onlineshop->offertool->orderstorage->offerClass . " not found.");
            }
            if (!class_exists($config->onlineshop->offertool->orderstorage->offerItemClass)) {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("OfferToolOfferItem class " . $config->onlineshop->offertool->orderstorage->offerItemClass . " not found.");
            }
        }
    }


    /**
     * @param \Zend_Config $config
     *
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    private function configurePaymentManager(\Zend_Config $config)
    {
        if (!empty($config->onlineshop->paymentmanager->class))
        {
            if (class_exists($config->onlineshop->paymentmanager->class))
            {
                $this->paymentManager = new $config->onlineshop->paymentmanager->class($config->onlineshop->paymentmanager->config);
                if (!($this->paymentManager instanceof \OnlineShop\Framework\PaymentManager\IPaymentManager))
                {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("PaymentManager class " . $config->onlineshop->paymentmanager->class . ' does not implement \OnlineShop\Framework\PaymentManager\IPaymentManager.');
                }
            }
            else
            {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("PaymentManager class " . $config->onlineshop->paymentmanager->class . " not found.");
            }
        }
    }


    /**
     * @param \Zend_Config $config
     *
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    private function configureOrderManager(\Zend_Config $config)
    {
        if (!empty($config->onlineshop->ordermanager->class))
        {
            if (class_exists($config->onlineshop->ordermanager->class))
            {
                $this->orderManager = new $config->onlineshop->ordermanager->class( $config->onlineshop->ordermanager->config );
                if (!($this->orderManager instanceof \OnlineShop\Framework\OrderManager\IOrderManager))
                {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("OrderManager class " . $config->onlineshop->ordermanager->class . " does not implement OnlineShop\\Framework\\OrderManager\\IOrderManager.");
                }
            }
            else
            {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("OrderManager class " . $config->onlineshop->ordermanager->class . " not found.");
            }
        }
    }


    public function getCartManager() {
        return $this->cartManager;
    }

    /**
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @param string $name optional name of checkout manager, in case there are more than one configured
     * @return \OnlineShop\Framework\CheckoutManager\ICheckoutManager
     */
    public function getCheckoutManager(\OnlineShop\Framework\CartManager\ICart $cart, $name = null) {

        if(empty($this->checkoutManagers[$cart->getId()])) {
            if($name) {
                $managerConfigName = "checkoutmanager_" . $name;
                $manager = new $this->config->onlineshop->$managerConfigName->class($cart, $this->config->onlineshop->$managerConfigName->config);
                if (!($manager instanceof \OnlineShop\Framework\CheckoutManager\ICheckoutManager)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("Checkoutmanager class " . $this->config->onlineshop->$managerConfigName->class . ' does not implement \OnlineShop\Framework\CheckoutManager\ICheckoutManager.');
                }
            } else {
                $manager = new $this->config->onlineshop->checkoutmanager->class($cart, $this->config->onlineshop->checkoutmanager->config);
                if (!($manager instanceof \OnlineShop\Framework\CheckoutManager\ICheckoutManager)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("Checkoutmanager class " . $this->config->onlineshop->checkoutmanager->class . ' does not implement \OnlineShop\Framework\CheckoutManager\ICheckoutManager.');
                }
            }

            $this->checkoutManagers[$cart->getId()] = $manager;
        }

        return $this->checkoutManagers[$cart->getId()];
    }

    /**
     * @param string $checkoutManagerName
     * @return \OnlineShop\Framework\CheckoutManager\ICommitOrderProcessor
     */
    public function getCommitOrderProcessor($checkoutManagerName = null) {
        $originalConfig = $this->config->onlineshop->checkoutmanager->config;
        if($checkoutManagerName) {
            $managerConfigName = "checkoutmanager_" . $checkoutManagerName;
            $originalConfig = $this->config->onlineshop->$managerConfigName->config;
        }

        $config = new \OnlineShop\Framework\Tools\Config\HelperContainer($originalConfig, "checkoutmanager");
        $commitOrderProcessorClassname = $config->commitorderprocessor->class;

        $commitOrderProcessor = new $commitOrderProcessorClassname();
        $commitOrderProcessor->setConfirmationMail((string)$config->mails->confirmation);
        return $commitOrderProcessor;
    }

    /**
     * @return \OnlineShop\Framework\IEnvironment
     */
    public function getEnvironment() {
        if(!$this->environment) {
            $this->initEnvironment();
        }
        return $this->environment;
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param null $name
     * @return \OnlineShop\Framework\PriceSystem\IPriceSystem
     */
    public function getPriceSystem($name = null) {
        if ($name == null) {
            $name = "default";
        }

        if ($ps = $this->priceSystems->$name) {
            return $ps;
        }
        else {
            throw new \OnlineShop\Framework\Exception\UnsupportedException("priceSystem " . $name . " is not supported, check configuration!");
        }

    }
    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param null $name
     * @return \OnlineShop\Framework\AvailabilitySystem\IAvailabilitySystem
     */
    public function getAvailabilitySystem($name = null) {
        if ($name == null) {
            $name = "default";
        }

        if ($ps = $this->availabilitySystems->$name) {
            return $ps;
        }
        else {
            throw new \OnlineShop\Framework\Exception\UnsupportedException("availabilitySystem " . $name . " is not supported, check configuration!");
        }

    }


    /**
     * @var \OnlineShop\Framework\IndexService\IndexService
     */
    private $indexService = null;

    /**
     * @return \OnlineShop\Framework\IndexService\IndexService
     */
    public function getIndexService() {
        if(empty($this->indexService)) {
            $this->indexService = new \OnlineShop\Framework\IndexService\IndexService($this->config->onlineshop->productindex);
        }
        return $this->indexService;
    }


    /**
     * @return string[]
     */
    public function getAllTenants() {
        if(empty($this->allTenants) && $this->config->onlineshop->productindex->tenants && $this->config->onlineshop->productindex->tenants instanceof \Zend_Config) {
            foreach($this->config->onlineshop->productindex->tenants as $name => $tenant) {
                $this->allTenants[$name] = $name;
            }
        }
        return $this->allTenants;
    }


    /**
     * @param \Zend_View $view
     *
     * @return \OnlineShop\Framework\FilterService\FilterService
     */
    public function getFilterService(\Zend_View $view) {

        $filterTypes = $this->getIndexService()->getCurrentTenantConfig()->getFilterTypeConfig();
        if(!$filterTypes)
        {
            $filterTypes = $this->config->onlineshop->filtertypes;
        }

        return new \OnlineShop\Framework\FilterService\FilterService($filterTypes, $view);
    }



    public function saveState() {
        $this->cartManager->save();
        $this->environment->save();
    }


    /**
     * @return \OnlineShop\Framework\PricingManager\IPricingManager
     */
    public function getPricingManager()
    {
        return $this->pricingManager;
    }

    /**
     * @return \OnlineShop\Framework\OfferTool\IService
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
     * @return \OnlineShop\Framework\PaymentManager\IPaymentManager
     */
    public function getPaymentManager()
    {
        return $this->paymentManager;
    }


    /**
     * @return \OnlineShop\Framework\OrderManager\IOrderManager
     */
    public function getOrderManager()
    {
        return $this->orderManager;
    }


    /**
     * @return \OnlineShop\Framework\VoucherService\IVoucherService
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getVoucherService() {

        if(empty($this->voucherService)) {
            $this->voucherService = new $this->config->onlineshop->voucherservice->class($this->config->onlineshop->voucherservice->config);
            if (!($this->voucherService instanceof \OnlineShop\Framework\VoucherService\IVoucherService)) {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Voucher Service class " . $this->config->onlineshop->voucherservice->class . ' does not implement \OnlineShop\Framework\VoucherService\IVoucherService.');
            }

        }

        return $this->voucherService;
    }


    /**
     * @param \OnlineShop\Framework\Model\AbstractVoucherTokenType $configuration
     * @return \OnlineShop\Framework\VoucherService\TokenManager\ITokenManager
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getTokenManager(\OnlineShop\Framework\Model\AbstractVoucherTokenType $configuration) {
        $id   = $configuration->getObject()->getId();
        $type = $configuration->getType();

        if(empty($this->tokenManagers[$id])) {

            $tokenManagerClass = $this->config->onlineshop->voucherservice->tokenmanagers->$type;

            if($tokenManagerClass) {
                $tokenManager = new $tokenManagerClass->class($configuration);
                if (!($tokenManager instanceof \OnlineShop\Framework\VoucherService\TokenManager\ITokenManager)) {
                    throw new \OnlineShop\Framework\Exception\InvalidConfigException("Token Manager class " . $tokenManagerClass->class . ' does not implement \OnlineShop\Framework\VoucherService\TokenManager\ITokenManager.');
                }

                $this->tokenManagers[$id] = $tokenManager;

            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Token Manager for " . $type . " not defined.");
            }

        }
        return $this->tokenManagers[$id];

    }

}
