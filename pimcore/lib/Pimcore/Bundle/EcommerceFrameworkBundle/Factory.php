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
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\IVoucherService;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ITokenManager;
use Pimcore\Config\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Factory
{
    /**
     * framework configuration file
     */
    const CONFIG_PATH = PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . '/EcommerceFrameworkConfig.php';

    /**
     * @var Factory
     */
    private static $instance;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Tenant specific cart managers
     *
     * @var ICartManager[]
     */
    private $cartManagers = [];

    /**
     * Tenant specific order managers
     *
     * @var IOrderManager[]
     */
    private $orderManagers;

    /**
     * @var Config
     */
    private $config;

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
     * @var ITokenManager[]
     */
    private $tokenManagers = [];


    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init();
        }

        return self::$instance;
    }

    private function get(string $serviceId)
    {
        return $this->container->get($serviceId);
    }

    /**
     * creates new factory instance and optionally resets environment too
     *
     * @param bool|true $keepEnvironment
     *
     * @return Factory
     */
    public static function resetInstance($keepEnvironment = true)
    {
        if ($keepEnvironment) {
            $environment = self::$instance->getEnvironment();
        } else {
            $environment = null;
        }

        self::$instance = new self($environment);
        self::$instance->init();

        return self::$instance;
    }

    private function __construct($environment = null)
    {
        $this->environment = $environment;

        // TODO this is only temporary
        $this->container = \Pimcore::getContainer();
    }

    public function getConfig()
    {
        if (empty($this->config)) {
            $this->config = new Config(require self::CONFIG_PATH, true);
        }

        return $this->config;
    }

    private function init()
    {
        $config = $this->getConfig();
        $this->checkConfig($config);
    }

    private function checkConfig($config)
    {
        $this->configurePriceSystem($config);
        $this->configureAvailabilitySystem($config);

        $this->configureCheckoutManager($config);
        $this->configurePricingManager($config);
        $this->configurePaymentManager($config);

        $this->configureOfferToolService($config);
    }

    public function getTrackingManager(): ITrackingManager
    {
        return $this->get('pimcore_ecommerce.tracking.tracking_manager');
    }

    private function configurePriceSystem($config)
    {
        if (empty($config->ecommerceframework->pricesystems)) {
            throw new InvalidConfigException('No Pricesystems defined.');
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
                    throw new InvalidConfigException('No Pricesystem class defined.');
                }
                if (empty($priceSystemConfig->name)) {
                    throw new InvalidConfigException('No Pricesystem name defined.');
                }
                $name = $priceSystemConfig->name;
                if (!empty($this->priceSystems->$name)) {
                    throw new InvalidConfigException('More than one Pricesystem '.$name . ' is defined!');
                }
                /* if (!class_exists($priceSystemConfig->class)) {
                    throw new InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . "  not found.");
                }*/
                $class = $priceSystemConfig->class;
                $priceSystem = new $class($priceSystemConfig->config);
                if (!$priceSystem instanceof IPriceSystem) {
                    throw new InvalidConfigException('Pricesystem class ' . $priceSystemConfig->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem.');
                }
                $this->priceSystems->$name=$priceSystem;
            }
        }
    }

    private function configureAvailabilitySystem($config)
    {
        if (empty($config->ecommerceframework->availablitysystems)) {
            throw new InvalidConfigException('No AvailabilitySystem defined.');
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
                    throw new InvalidConfigException('No AvailabilitySystem class defined.');
                }
                if (empty($availabilitySystemConfig->name)) {
                    throw new InvalidConfigException('No AvailabilitySystem name defined.');
                }
                $name = $availabilitySystemConfig->name;
                if (!empty($this->availablitysystems->$name)) {
                    throw new InvalidConfigException('More than one AvailabilitySystem '.$name . ' is defined!');
                }
                /* if (!class_exists($priceSystemConfig->class)) {
                    throw new InvalidConfigException("Pricesystem class " . $priceSystemConfig->class . "  not found.");
                }*/

                $class = $availabilitySystemConfig->class;
                $availabilitySystem = new $class();
                if (! $availabilitySystem instanceof IAvailabilitySystem) {
                    throw new InvalidConfigException('AvailabilitySystem class ' . $availabilitySystemConfig->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailabilitySystem.');
                }
                $this->availabilitySystems->$name= $availabilitySystem;
            }
        }
    }

    private function configureCheckoutManager($config)
    {
        if (empty($config->ecommerceframework->checkoutmanager->class)) {
            throw new InvalidConfigException('No Checkoutmanager class defined.');
        } else {
            if (!class_exists($config->ecommerceframework->checkoutmanager->class)) {
                throw new InvalidConfigException('Checkoutmanager class ' . $config->ecommerceframework->checkoutmanager->class . ' not found.');
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
            throw new InvalidConfigException('No PricingManager class defined.');
        } else {
            if (class_exists($config->ecommerceframework->pricingmanager->class)) {
                $this->pricingManager = new $config->ecommerceframework->pricingmanager->class($config->ecommerceframework->pricingmanager->config, \Pimcore::getContainer()->get('session'));
                if (!($this->pricingManager instanceof IPricingManager)) {
                    throw new InvalidConfigException('PricingManager class ' . $config->ecommerceframework->pricingmanager->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPricingManager.');
                }
            } else {
                throw new InvalidConfigException('PricingManager class ' . $config->ecommerceframework->pricingmanager->class . ' not found.');
            }
        }
    }

    private function configureOfferToolService($config)
    {
        if (!empty($config->ecommerceframework->offertool->class)) {
            if (!class_exists($config->ecommerceframework->offertool->class)) {
                throw new InvalidConfigException('OfferTool class ' . $config->ecommerceframework->offertool->class . ' not found.');
            }
            if (!class_exists($config->ecommerceframework->offertool->orderstorage->offerClass)) {
                throw new InvalidConfigException('OfferToolOffer class ' . $config->ecommerceframework->offertool->orderstorage->offerClass . ' not found.');
            }
            if (!class_exists($config->ecommerceframework->offertool->orderstorage->offerItemClass)) {
                throw new InvalidConfigException('OfferToolOfferItem class ' . $config->ecommerceframework->offertool->orderstorage->offerItemClass . ' not found.');
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
                    throw new InvalidConfigException('PaymentManager class ' . $config->ecommerceframework->paymentmanager->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IPaymentManager.');
                }
            } else {
                throw new InvalidConfigException('PaymentManager class ' . $config->ecommerceframework->paymentmanager->class . ' not found.');
            }
        }
    }

    public function getCartManager(string $tenant = null): ICartManager
    {
        if (null === $tenant) {
            $tenant = $this->getEnvironment()->getCurrentCheckoutTenant() ?? 'default';
        }

        if (!isset($this->cartManagers[$tenant])) {
            $this->cartManagers[$tenant] = $this->get(sprintf('pimcore_ecommerce.cart_manager.%s', $tenant));
        }

        return $this->cartManagers[$tenant];
    }

    /**
     * @throws InvalidConfigException
     *
     * @param ICart $cart
     * @param string $name optional name of checkout manager, in case there are more than one configured
     *
     * @return ICheckoutManager
     */
    public function getCheckoutManager(ICart $cart, $name = null)
    {
        if (empty($this->checkoutManagers[$cart->getId()])) {
            if ($name) {
                $managerConfigName = 'checkoutmanager_' . $name;
                $manager = new $this->config->ecommerceframework->$managerConfigName->class($cart, $this->config->ecommerceframework->$managerConfigName->config);
                if (!($manager instanceof ICheckoutManager)) {
                    throw new InvalidConfigException('Checkoutmanager class ' . $this->config->ecommerceframework->$managerConfigName->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManager.');
                }
            } else {
                $manager = new $this->config->ecommerceframework->checkoutmanager->class($cart, $this->config->ecommerceframework->checkoutmanager->config);
                if (!($manager instanceof ICheckoutManager)) {
                    throw new InvalidConfigException('Checkoutmanager class ' . $this->config->ecommerceframework->checkoutmanager->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManager.');
                }
            }

            $this->checkoutManagers[$cart->getId()] = $manager;
        }

        return $this->checkoutManagers[$cart->getId()];
    }

    /**
     * @param string $checkoutManagerName
     *
     * @return ICommitOrderProcessor
     */
    public function getCommitOrderProcessor($checkoutManagerName = null)
    {
        $originalConfig = $this->config->ecommerceframework->checkoutmanager->config;
        if ($checkoutManagerName) {
            $managerConfigName = 'checkoutmanager_' . $checkoutManagerName;
            $originalConfig = $this->config->ecommerceframework->$managerConfigName->config;
        }

        $config = new HelperContainer($originalConfig, 'checkoutmanager');
        $commitOrderProcessorClassname = $config->commitorderprocessor->class;

        $commitOrderProcessor = new $commitOrderProcessorClassname();
        $commitOrderProcessor->setConfirmationMail((string)$config->mails->confirmation);

        return $commitOrderProcessor;
    }

    public function getEnvironment(): IEnvironment
    {
        return $this->get('pimcore_ecommerce.environment');
    }

    /**
     * @throws UnsupportedException
     *
     * @param null $name
     *
     * @return IPriceSystem
     */
    public function getPriceSystem($name = null)
    {
        if ($name == null) {
            $name = 'default';
        }

        if ($ps = $this->priceSystems->$name) {
            return $ps;
        } else {
            throw new UnsupportedException('priceSystem ' . $name . ' is not supported, check configuration!');
        }
    }

    /**
     * @throws UnsupportedException
     *
     * @param null $name
     *
     * @return IAvailabilitySystem
     */
    public function getAvailabilitySystem($name = null)
    {
        if ($name == null) {
            $name = 'default';
        }

        if ($ps = $this->availabilitySystems->$name) {
            return $ps;
        } else {
            throw new UnsupportedException('availabilitySystem ' . $name . ' is not supported, check configuration!');
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
        $this->getCartManager()->save();
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
     * @param string|null $tenant
     *
     * @return IOrderManager
     */
    public function getOrderManager(string $tenant = null): IOrderManager
    {
        if (null === $tenant) {
            $tenant = $this->getEnvironment()->getCurrentCheckoutTenant() ?? 'default';
        }

        if (!isset($this->orderManagers[$tenant])) {
            $this->orderManagers[$tenant] = $this->get(sprintf('pimcore_ecommerce.order_manager.%s', $tenant));
        }

        return $this->orderManagers[$tenant];
    }

    /**
     * @return IVoucherService
     */
    public function getVoucherService(): IVoucherService
    {
        return $this->get('pimcore_ecommerce.voucher_service');
    }

    /**
     * @param AbstractVoucherTokenType $configuration
     *
     * @return ITokenManager
     *
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
                    throw new InvalidConfigException('Token Manager class ' . $tokenManagerClass->class . ' does not implement \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ITokenManager.');
                }

                $this->tokenManagers[$id] = $tokenManager;
            } else {
                throw new InvalidConfigException('Token Manager for ' . $type . ' not defined.');
            }
        }

        return $this->tokenManagers[$id];
    }
}
