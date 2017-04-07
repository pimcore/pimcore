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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailabilitySystem;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICommitOrderProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterService;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\IndexService;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType;
use Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\IService;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IPaymentManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPricingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Config\HelperContainer;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITrackingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\IVoucherService;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ITokenManager;
use Pimcore\Config\Config;

class Factory
{

    /**
     * framework configuration file
     */
    const CONFIG_PATH = PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/EcommerceFrameworkConfig.php";

    /**
     * @var Factory
     */
    private static $instance;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ICartManager
     */
    private $cartManager;

    /**
     * @var IPriceSystem
     */
    private $priceSystems;

    /**
     * @var IAvailabilitySystem
     */
    private $availabilitySystems;

    /**
     * @var ICheckoutManager
     */
    private $checkoutManagers;

    /**
     * @var IPricingManager
     */
    private $pricingManager;

    /**
     * @var IOrderManager
     */
    private $orderManager;

    /**
     * @var  IService
     */
    private $offerToolService;

    /**
     * @var string[]
     */
    private $allTenants;

    /**
     * @var IEnvironment
     */
    private $environment;

    /**
     * @var IPaymentManager
     */
    private $paymentManager;


    /**
     * @var IVoucherService
     */
    private $voucherService;

    /**
     * @var ITokenManager[]
     */
    private $tokenManagers = [];

    /**
     * @var TrackingManager
     */
    private $trackingManager;

    public static function getInstance()
    {
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
    public static function resetInstance($keepEnvironment = true)
    {
        if ($keepEnvironment) {
            $environment = self::$instance->getEnvironment();
        } else {
            $environment = null;
        }

        self::$instance = new Factory($environment);
        self::$instance->init();

        return self::$instance;
    }

    private function __construct($environment = null)
    {
        $this->environment = $environment;
    }

    public function getConfig()
    {
        if (empty($this->config)) {
            $this->config = new Config(require self::CONFIG_PATH, true);
        }

        return $this->config;
    }

    private function initEnvironment()
    {
        $config = $this->getConfig();

        //Environment
        if (empty($config->ecommerceframework->environment->class)) {
            throw new InvalidConfigException("No Environment class defined.");
        } else {
            if (class_exists($config->ecommerceframework->environment->class)) {
                $session = \Pimcore::getContainer()->get('session');
                $localeService = \Pimcore::getContainer()->get('pimcore.locale');

                $this->environment = new $config->ecommerceframework->environment->class($config->ecommerceframework->environment->config, $session, $localeService);
                if (!($this->environment instanceof IEnvironment)) {
                    throw new InvalidConfigException("Environment class " . $config->ecommerceframework->environment->class . ' does not implement IEnvironment.');
                }
            } else {
                throw new InvalidConfigException("Environment class " . $config->ecommerceframework->environment->class . " not found.");
            }
        }
    }

    private function init()
    {
        $config = $this->getConfig();
        $this->checkConfig($config);
    }

    private function checkConfig($config)
    {
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

    private function configureCartManager($config)
    {
        if (empty($config->ecommerceframework->cartmanager->class)) {
            throw new InvalidConfigException("No Cartmanager class defined.");
        } else {
            if (class_exists($config->ecommerceframework->cartmanager->class)) {
                $this->cartManager = new $config->ecommerceframework->cartmanager->class($config->ecommerceframework->cartmanager->config);
                if (!($this->cartManager instanceof ICartManager)) {
                    throw new InvalidConfigException("Cartmanager class " . $config->ecommerceframework->cartmanager->class . " does not implement ICartManager.");
                }
            } else {
                throw new InvalidConfigException("Cartmanager class " . $config->ecommerceframework->cartmanager->class . " not found.");
            }
        }
    }

    /**
     * Configure tracking manager
     *
     * @param Config $config
     * @throws InvalidConfigException
     */
    private function configureTrackingManager(Config $config)
    {
        if (!empty($config->ecommerceframework->trackingmanager->class)) {
            $trackingManagerClass = $config->ecommerceframework->trackingmanager->class;
            if (class_exists($trackingManagerClass)) {
                $instance = new $trackingManagerClass($config->ecommerceframework->trackingmanager->config, \Pimcore::getContainer()->get('templating'));
                if ($instance instanceof ITrackingManager) {
                    $this->trackingManager = $instance;
                } else {
                    throw new InvalidConfigException(sprintf('TrackingManager class %s does not implement Pimcore\\Bundle\\EcommerceFrameworkBundle\\Tracking\\ITrackingManager', $trackingManagerClass));
                }
            } else {
                throw new InvalidConfigException(sprintf('TrackingManager class %s not found.', $trackingManagerClass));
            }
        }
    }

    /**
     * Get tracking manager
     *
     * @return TrackingManager
     * @throws UnsupportedException
     */
    public function getTrackingManager()
    {
        if (null === $this->trackingManager) {
            throw new UnsupportedException('Tracking is not configured, check configuration!');
        }

        return $this->trackingManager;
    }

    private function configurePriceSystem($config)
    {
        if (empty($config->ecommerceframework->pricesystems)) {
            throw new InvalidConfigException("No Pricesystems defined.");
        }
        //$this->priceSystems=array();
        $priceSystemConfigs = $config->ecommerceframework->pricesystems->pricesystem;
        if ($priceSystemConfigs->class) {
            $priceSystemConfigs = [$priceSystemConfigs];
        }

        $this->priceSystems = new \stdClass();
        if (!empty($priceSystemConfigs)) {
            foreach ($priceSystemConfigs as $priceSystemConfig) {
                if (empty($priceSystemConfig->class)) {
                    throw new InvalidConfigException("No Pricesystem class defined.");
                }
                if (empty($priceSystemConfig->name)) {
                    throw new InvalidConfigException("No Pricesystem name defined.");
                }
                $name = $priceSystemConfig->name;
                if (!empty($this->priceSystems->$name)) {
                    throw new InvalidConfigException("More than one Pricesystem ".$name . " is defined!");
                }
                /* if (!class_exists($priceSystemConfig->class)) {
                    throw new InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . "  not found.");
                }*/
                $class = $priceSystemConfig->class;
                $priceSystem = new $class($priceSystemConfig->config);
                if (!$priceSystem instanceof IPriceSystem) {
                    throw new InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem.');
                }
                $this->priceSystems->$name=$priceSystem;
            }
        }
    }
    private function configureAvailabilitySystem($config)
    {
        if (empty($config->ecommerceframework->availablitysystems)) {
            throw new InvalidConfigException("No AvailabilitySystem defined.");
        }
        //$this->priceSystems=array();
        $availabilitySystemConfigs = $config->ecommerceframework->availablitysystems->availablitysystem;
        if ($availabilitySystemConfigs->class) {
            $availabilitySystemConfigs = [$availabilitySystemConfigs];
        }

        $this->availabilitySystems = new \stdClass();
        if (!empty($availabilitySystemConfigs)) {
            foreach ($availabilitySystemConfigs as $availabilitySystemConfig) {
                if (empty($availabilitySystemConfig->class)) {
                    throw new InvalidConfigException("No AvailabilitySystem class defined.");
                }
                if (empty($availabilitySystemConfig->name)) {
                    throw new InvalidConfigException("No AvailabilitySystem name defined.");
                }
                $name = $availabilitySystemConfig->name;
                if (!empty($this->availablitysystems->$name)) {
                    throw new InvalidConfigException("More than one AvailabilitySystem ".$name . " is defined!");
                }
                /* if (!class_exists($priceSystemConfig->class)) {
                    throw new InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . "  not found.");
                }*/

                $class = $availabilitySystemConfig->class;
                $availabilitySystem = new $class();
                if (! $availabilitySystem instanceof IAvailabilitySystem) {
                    throw new InvalidConfigException("AvailabilitySystem class " . $availabilitySystemConfig->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailabilitySystem.');
                }
                $this->availabilitySystems->$name= $availabilitySystem;
            }
        }
    }


    private function configureCheckoutManager($config)
    {
        if (empty($config->ecommerceframework->checkoutmanager->class)) {
            throw new InvalidConfigException("No Checkoutmanager class defined.");
        } else {
            if (!class_exists($config->ecommerceframework->checkoutmanager->class)) {
                throw new InvalidConfigException("Checkoutmanager class " . $config->ecommerceframework->checkoutmanager->class . " not found.");
            }
        }
    }

    /**
     * @param Config $config
     *
     * @throws InvalidConfigException
     */
    private function configurePricingManager(Config $config)
    {
        if (empty($config->ecommerceframework->pricingmanager->class)) {
            throw new InvalidConfigException("No PricingManager class defined.");
        } else {
            if (class_exists($config->ecommerceframework->pricingmanager->class)) {
                $this->pricingManager = new $config->ecommerceframework->pricingmanager->class($config->ecommerceframework->pricingmanager->config, \Pimcore::getContainer()->get('session'));
                if (!($this->pricingManager instanceof IPricingManager)) {
                    throw new InvalidConfigException("PricingManager class " . $config->ecommerceframework->pricingmanager->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPricingManager.');
                }
            } else {
                throw new InvalidConfigException("PricingManager class " . $config->ecommerceframework->pricingmanager->class . " not found.");
            }
        }
    }


    private function configureOfferToolService($config)
    {
        if (!empty($config->ecommerceframework->offertool->class)) {
            if (!class_exists($config->ecommerceframework->offertool->class)) {
                throw new InvalidConfigException("OfferTool class " . $config->ecommerceframework->offertool->class . " not found.");
            }
            if (!class_exists($config->ecommerceframework->offertool->orderstorage->offerClass)) {
                throw new InvalidConfigException("OfferToolOffer class " . $config->ecommerceframework->offertool->orderstorage->offerClass . " not found.");
            }
            if (!class_exists($config->ecommerceframework->offertool->orderstorage->offerItemClass)) {
                throw new InvalidConfigException("OfferToolOfferItem class " . $config->ecommerceframework->offertool->orderstorage->offerItemClass . " not found.");
            }
        }
    }


    /**
     * @param Config $config
     *
     * @throws InvalidConfigException
     */
    private function configurePaymentManager(Config $config)
    {
        if (!empty($config->ecommerceframework->paymentmanager->class)) {
            if (class_exists($config->ecommerceframework->paymentmanager->class)) {
                $this->paymentManager = new $config->ecommerceframework->paymentmanager->class($config->ecommerceframework->paymentmanager->config);
                if (!($this->paymentManager instanceof IPaymentManager)) {
                    throw new InvalidConfigException("PaymentManager class " . $config->ecommerceframework->paymentmanager->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IPaymentManager.');
                }
            } else {
                throw new InvalidConfigException("PaymentManager class " . $config->ecommerceframework->paymentmanager->class . " not found.");
            }
        }
    }


    /**
     * @param Config $config
     *
     * @throws InvalidConfigException
     */
    private function configureOrderManager(Config $config)
    {
        if (!empty($config->ecommerceframework->ordermanager->class)) {
            if (class_exists($config->ecommerceframework->ordermanager->class)) {
                $this->orderManager = new $config->ecommerceframework->ordermanager->class($config->ecommerceframework->ordermanager->config);
                if (!($this->orderManager instanceof IOrderManager)) {
                    throw new InvalidConfigException("OrderManager class " . $config->ecommerceframework->ordermanager->class . " does not implement Pimcore\\Bundle\\EcommerceFrameworkBundle\\OrderManager\\IOrderManager.");
                }
            } else {
                throw new InvalidConfigException("OrderManager class " . $config->ecommerceframework->ordermanager->class . " not found.");
            }
        }
    }


    public function getCartManager()
    {
        return $this->cartManager;
    }

    /**
     * @throws InvalidConfigException
     * @param ICart $cart
     * @param string $name optional name of checkout manager, in case there are more than one configured
     * @return ICheckoutManager
     */
    public function getCheckoutManager(ICart $cart, $name = null)
    {
        if (empty($this->checkoutManagers[$cart->getId()])) {
            if ($name) {
                $managerConfigName = "checkoutmanager_" . $name;
                $manager = new $this->config->ecommerceframework->$managerConfigName->class($cart, $this->config->ecommerceframework->$managerConfigName->config);
                if (!($manager instanceof ICheckoutManager)) {
                    throw new InvalidConfigException("Checkoutmanager class " . $this->config->ecommerceframework->$managerConfigName->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManager.');
                }
            } else {
                $manager = new $this->config->ecommerceframework->checkoutmanager->class($cart, $this->config->ecommerceframework->checkoutmanager->config);
                if (!($manager instanceof ICheckoutManager)) {
                    throw new InvalidConfigException("Checkoutmanager class " . $this->config->ecommerceframework->checkoutmanager->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManager.');
                }
            }

            $this->checkoutManagers[$cart->getId()] = $manager;
        }

        return $this->checkoutManagers[$cart->getId()];
    }

    /**
     * @param string $checkoutManagerName
     * @return ICommitOrderProcessor
     */
    public function getCommitOrderProcessor($checkoutManagerName = null)
    {
        $originalConfig = $this->config->ecommerceframework->checkoutmanager->config;
        if ($checkoutManagerName) {
            $managerConfigName = "checkoutmanager_" . $checkoutManagerName;
            $originalConfig = $this->config->ecommerceframework->$managerConfigName->config;
        }

        $config = new HelperContainer($originalConfig, "checkoutmanager");
        $commitOrderProcessorClassname = $config->commitorderprocessor->class;

        $commitOrderProcessor = new $commitOrderProcessorClassname();
        $commitOrderProcessor->setConfirmationMail((string)$config->mails->confirmation);

        return $commitOrderProcessor;
    }

    /**
     * @return IEnvironment
     */
    public function getEnvironment()
    {
        if (!$this->environment) {
            $this->initEnvironment();
        }

        return $this->environment;
    }

    /**
     * @throws UnsupportedException
     * @param null $name
     * @return IPriceSystem
     */
    public function getPriceSystem($name = null)
    {
        if ($name == null) {
            $name = "default";
        }

        if ($ps = $this->priceSystems->$name) {
            return $ps;
        } else {
            throw new UnsupportedException("priceSystem " . $name . " is not supported, check configuration!");
        }
    }
    /**
     * @throws UnsupportedException
     * @param null $name
     * @return IAvailabilitySystem
     */
    public function getAvailabilitySystem($name = null)
    {
        if ($name == null) {
            $name = "default";
        }

        if ($ps = $this->availabilitySystems->$name) {
            return $ps;
        } else {
            throw new UnsupportedException("availabilitySystem " . $name . " is not supported, check configuration!");
        }
    }


    /**
     * @var IndexService
     */
    private $indexService = null;

    /**
     * @return IndexService
     */
    public function getIndexService()
    {
        if (empty($this->indexService)) {
            $this->indexService = new IndexService($this->config->ecommerceframework->productindex);
        }

        return $this->indexService;
    }


    /**
     * @return string[]
     */
    public function getAllTenants()
    {
        if (empty($this->allTenants) && $this->config->ecommerceframework->productindex->tenants && $this->config->ecommerceframework->productindex->tenants instanceof Config) {
            foreach ($this->config->ecommerceframework->productindex->tenants as $name => $tenant) {
                $this->allTenants[$name] = $name;
            }
        }

        return $this->allTenants;
    }


    /**
     * @return FilterService
     */
    public function getFilterService()
    {
        $filterTypes = $this->getIndexService()->getCurrentTenantConfig()->getFilterTypeConfig();
        if (!$filterTypes) {
            $filterTypes = $this->config->ecommerceframework->filtertypes;
        }

        $translator = \Pimcore::getContainer()->get('translator');
        $renderer =  \Pimcore::getContainer()->get('templating');

        return new FilterService($filterTypes, $translator, $renderer);
    }



    public function saveState()
    {
        $this->cartManager->save();
        $this->environment->save();
    }


    /**
     * @return IPricingManager
     */
    public function getPricingManager()
    {
        return $this->pricingManager;
    }

    /**
     * @return IService
     */
    public function getOfferToolService()
    {
        if (empty($this->offerToolService)) {
            $className = (string)$this->config->ecommerceframework->offertool->class;
            $this->offerToolService = new $className(
                (string) $this->config->ecommerceframework->offertool->orderstorage->offerClass,
                (string) $this->config->ecommerceframework->offertool->orderstorage->offerItemClass,
                (string) $this->config->ecommerceframework->offertool->orderstorage->parentFolderPath
            );
        }

        return $this->offerToolService;
    }


    /**
     * @return IPaymentManager
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
     * @return IVoucherService
     * @throws InvalidConfigException
     */
    public function getVoucherService()
    {
        if (empty($this->voucherService)) {
            $this->voucherService = new $this->config->ecommerceframework->voucherservice->class($this->config->ecommerceframework->voucherservice->config);
            if (!($this->voucherService instanceof IVoucherService)) {
                throw new InvalidConfigException("Voucher Service class " . $this->config->ecommerceframework->voucherservice->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\IVoucherService.');
            }
        }

        return $this->voucherService;
    }


    /**
     * @param AbstractVoucherTokenType $configuration
     * @return ITokenManager
     * @throws InvalidConfigException
     */
    public function getTokenManager(AbstractVoucherTokenType $configuration)
    {
        $id   = $configuration->getObject()->getId();
        $type = $configuration->getType();

        if (empty($this->tokenManagers[$id])) {
            $tokenManagerClass = $this->config->ecommerceframework->voucherservice->tokenmanagers->$type;

            if ($tokenManagerClass) {
                $tokenManager = new $tokenManagerClass->class($configuration);
                if (!($tokenManager instanceof ITokenManager)) {
                    throw new InvalidConfigException("Token Manager class " . $tokenManagerClass->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ITokenManager.');
                }

                $this->tokenManagers[$id] = $tokenManager;
            } else {
                throw new InvalidConfigException("Token Manager for " . $type . " not defined.");
            }
        }

        return $this->tokenManagers[$id];
    }
}
