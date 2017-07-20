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
use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\PimcoreEcommerceFrameworkExtension;
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
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Factory
{
    /**
     * framework configuration file
     */
    const CONFIG_PATH = PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . '/EcommerceFrameworkConfig.php';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var IEnvironment
     */
    private $environment;

    /**
     * Tenant specific cart managers
     *
     * @var PsrContainerInterface
     */
    private $cartManagers;

    /**
     * Tenant specific order managers
     *
     * @var PsrContainerInterface
     */
    private $orderManagers;

    /**
     * Price systems registered by name
     *
     * @var PsrContainerInterface
     */
    private $priceSystems;

    /**
     * Availability systems registered by name
     *
     * @var PsrContainerInterface
     */
    private $availabilitySystems;

    /**
     * @var Config
     */
    private $config;

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
     * @var IPaymentManager
     */
    private $paymentManager;

    /**
     * Systems with multiple instances (e.g. price systems or tenant specific systems) are
     * injected through a service locator which is indexed by tenant/name. All other services
     * are loaded from the container on demand to make sure only services needed are built.
     *
     * @param ContainerInterface $container
     * @param PsrContainerInterface $cartManagers
     * @param PsrContainerInterface $orderManagers
     * @param PsrContainerInterface $priceSystemsLocator
     * @param PsrContainerInterface $availabilitySystems
     */
    public function __construct(
        ContainerInterface $container,
        PsrContainerInterface $cartManagers,
        PsrContainerInterface $orderManagers,
        PsrContainerInterface $priceSystemsLocator,
        PsrContainerInterface $availabilitySystems
    )
    {
        $this->container           = $container;
        $this->cartManagers        = $cartManagers;
        $this->orderManagers       = $orderManagers;
        $this->priceSystems        = $priceSystemsLocator;
        $this->availabilitySystems = $availabilitySystems;

        $this->init();
    }

    public static function getInstance(): self
    {
        return \Pimcore::getContainer()->get(Factory::class);
    }

    public function getEnvironment(): IEnvironment
    {
        return $this->container->get(PimcoreEcommerceFrameworkExtension::SERVICE_ID_ENVIRONMENT);
    }

    /**
     * Returns cart manager for a specific tenant. If no tenant is passed it will fall back to the current
     * checkout tenant or to "default" if no current checkout tenant is set.
     *
     * @param string|null $tenant
     *
     * @return ICartManager
     * @throws UnsupportedException
     */
    public function getCartManager(string $tenant = null): ICartManager
    {
        if (null === $tenant) {
            $tenant = $this->getEnvironment()->getCurrentCheckoutTenant() ?? 'default';
        }

        if (!$this->cartManagers->has($tenant)) {
            throw new UnsupportedException(sprintf(
                'Cart manager for tenant "%s" is not defined. Please check the configuration.',
                $tenant
            ));
        }

        return $this->cartManagers->get($tenant);
    }

    /**
     * Returns order manager for a specific tenant. If no tenant is passed it will fall back to the current
     * checkout tenant or to "default" if no current checkout tenant is set.
     *
     * @param string|null $tenant
     *
     * @return IOrderManager
     * @throws UnsupportedException
     */
    public function getOrderManager(string $tenant = null): IOrderManager
    {
        if (null === $tenant) {
            $tenant = $this->getEnvironment()->getCurrentCheckoutTenant() ?? 'default';
        }

        if (!$this->orderManagers->has($tenant)) {
            throw new UnsupportedException(sprintf(
                'Order manager for tenant "%s" is not defined. Please check the configuration.',
                $tenant
            ));
        }

        return $this->orderManagers->get($tenant);
    }

    /**
     * Returns a price system by name. Falls back to "default" if no name is passed.
     *
     * @param string|null $name
     *
     * @return IPriceSystem
     * @throws UnsupportedException
     */
    public function getPriceSystem(string $name = null): IPriceSystem
    {
        if (null === $name) {
            $name = 'default';
        }

        if (!$this->priceSystems->has($name)) {
            throw new UnsupportedException(sprintf(
                'Price system "%s" is not supported. Please check the configuration.',
                $name
            ));
        }

        return $this->priceSystems->get($name);
    }

    /**
     * Returns an availability system by name. Falls back to "default" if no name is passed.
     *
     * @param string|null $name
     *
     * @return IAvailabilitySystem
     * @throws UnsupportedException
     */
    public function getAvailabilitySystem(string $name = null): IAvailabilitySystem
    {
        if (null === $name) {
            $name = 'default';
        }

        if (!$this->availabilitySystems->has($name)) {
            throw new UnsupportedException(sprintf(
                'Availability system "%s" is not supported. Please check the configuration.',
                $name
            ));
        }

        return $this->availabilitySystems->get($name);
    }

    public function getVoucherService(): IVoucherService
    {
        return $this->container->get(PimcoreEcommerceFrameworkExtension::SERVICE_ID_VOUCHER_SERVICE);
    }

    /**
     * Builds a token manager for a specific token configuration
     *
     * @param AbstractVoucherTokenType $configuration
     *
     * @return ITokenManager
     */
    public function getTokenManager(AbstractVoucherTokenType $configuration): ITokenManager
    {
        $tokenManagerFactory = $this->container->get(PimcoreEcommerceFrameworkExtension::SERVICE_ID_TOKEN_MANAGER_FACTORY);

        return $tokenManagerFactory->getTokenManager($configuration);
    }

    public function getTrackingManager(): ITrackingManager
    {
        return $this->container->get(PimcoreEcommerceFrameworkExtension::SERVICE_ID_TRACKING_MANAGER);
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
        throw new \RuntimeException(__METHOD__ . ' is not implemented anymore');

        if ($keepEnvironment) {
            $environment = self::$instance->getEnvironment();
        } else {
            $environment = null;
        }

        self::$instance = new self($environment);
        self::$instance->init();

        return self::$instance;
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
        $this->configureCheckoutManager($config);
        $this->configurePricingManager($config);
        $this->configurePaymentManager($config);

        $this->configureOfferToolService($config);
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

}
