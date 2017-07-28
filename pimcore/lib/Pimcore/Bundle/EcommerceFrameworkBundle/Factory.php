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
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManagerFactory;
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
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITrackingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\IVoucherService;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ITokenManager;
use Pimcore\Config\Config;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Factory
{
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
     * Checkout manager factories registered by tenant
     *
     * @var PsrContainerInterface
     */
    private $checkoutManagerFactories;

    /**
     * Commit order processors registered by tenant
     *
     * @var PsrContainerInterface
     */
    private $commitOrderProcessors;

    /**
     * Filter services registered by ^tenant
     *
     * @var PsrContainerInterface
     */
    private $filterServices;

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
     * @param PsrContainerInterface $checkoutManagerFactories
     * @param PsrContainerInterface $commitOrderProcessors
     * @param PsrContainerInterface $filterServices
     */
    public function __construct(
        ContainerInterface $container,
        PsrContainerInterface $cartManagers,
        PsrContainerInterface $orderManagers,
        PsrContainerInterface $priceSystemsLocator,
        PsrContainerInterface $availabilitySystems,
        PsrContainerInterface $checkoutManagerFactories,
        PsrContainerInterface $commitOrderProcessors,
        PsrContainerInterface $filterServices
    )
    {
        $this->container                = $container;
        $this->cartManagers             = $cartManagers;
        $this->orderManagers            = $orderManagers;
        $this->priceSystems             = $priceSystemsLocator;
        $this->availabilitySystems      = $availabilitySystems;
        $this->checkoutManagerFactories = $checkoutManagerFactories;
        $this->commitOrderProcessors    = $commitOrderProcessors;
        $this->filterServices           = $filterServices;
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
        $tenant = $this->resolveCheckoutTenant($tenant);

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
        $tenant = $this->resolveCheckoutTenant($tenant);

        if (!$this->orderManagers->has($tenant)) {
            throw new UnsupportedException(sprintf(
                'Order manager for tenant "%s" is not defined. Please check the configuration.',
                $tenant
            ));
        }

        return $this->orderManagers->get($tenant);
    }

    public function getPricingManager(): IPricingManager
    {
        return $this->container->get(PimcoreEcommerceFrameworkExtension::SERVICE_ID_PRICING_MANAGER);
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
        if (empty($name)) {
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
        if (empty($name)) {
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

    /**
     * Returns checkout manager for a specific tenant. If no tenant is passed it will fall back to the current
     * checkout tenant or to "default" if no current checkout tenant is set.
     *
     * @param ICart $cart
     * @param string|null $tenant
     *
     * @return ICheckoutManager
     * @throws UnsupportedException
     */
    public function getCheckoutManager(ICart $cart, string $tenant = null): ICheckoutManager
    {
        $tenant = $this->resolveCheckoutTenant($tenant);

        if (!$this->checkoutManagerFactories->has($tenant)) {
            throw new UnsupportedException(sprintf(
                'There is no factory defined for checkout manager tenant "%s". Please check the configuration.',
                $tenant
            ));
        }

        /** @var ICheckoutManagerFactory $factory */
        $factory = $this->checkoutManagerFactories->get($tenant);

        return $factory->createCheckoutManager($cart);
    }

    /**
     * Returns a commit order processor which is configured for a specific checkout manager
     *
     * @param string|null $tenant
     *
     * @return ICommitOrderProcessor
     * @throws UnsupportedException
     */
    public function getCommitOrderProcessor(string $tenant = null): ICommitOrderProcessor
    {
        $tenant = $this->resolveCheckoutTenant($tenant);

        if (!$this->commitOrderProcessors->has($tenant)) {
            throw new UnsupportedException(sprintf(
                'Commit order processor for checkout manager tenant "%s" is not defined. Please check the configuration.',
                $tenant
            ));
        }

        return $this->commitOrderProcessors->get($tenant);
    }

    public function getPaymentManager(): IPaymentManager
    {
        return $this->container->get(PimcoreEcommerceFrameworkExtension::SERVICE_ID_PAYMENT_MANAGER);
    }

    /**
     * Returns the index service which holds a collection of all index workers
     *
     * @return IndexService
     */
    public function getIndexService(): IndexService
    {
        return $this->container->get(PimcoreEcommerceFrameworkExtension::SERVICE_ID_INDEX_SERVICE);
    }

    /**
     * Returns the filter service for the currently set assortment tenant. Falls back to "default" if no tenant is passed
     * and there is no current assortment tenant set.
     *
     * @param string|null $tenant
     *
     * @return FilterService
     * @throws UnsupportedException
     */
    public function getFilterService(string $tenant = null): FilterService
    {
        $tenant = $this->resolveAssortmentTenant($tenant);

        if (!$this->filterServices->has($tenant)) {
            throw new UnsupportedException(sprintf(
                'Filter service for assortment tenant "%s" is not registered. Please check the configuration.',
                $tenant
            ));
        }

        return $this->filterServices->get($tenant);
    }

    public function getAllTenants(): array
    {
        return $this->getIndexService()->getTenants();
    }

    public function getOfferToolService(): IService
    {
        return $this->container->get(PimcoreEcommerceFrameworkExtension::SERVICE_ID_OFFER_TOOL);
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

    public function saveState()
    {
        $this->getCartManager()->save();
        $this->environment->save();
    }

    private function resolveAssortmentTenant(string $tenant = null): string
    {
        // explicitely checking for empty here to catch situations where the tenant is just an empty string
        if (empty($tenant)) {
            $tenant = $this->getEnvironment()->getCurrentAssortmentTenant();
        }

        if (!empty($tenant)) {
            return $tenant;
        }

        return 'default';
    }

    private function resolveCheckoutTenant(string $tenant = null): string
    {
        // explicitely checking for empty here to catch situations where the tenant is just an empty string
        if (empty($tenant)) {
            $tenant = $this->getEnvironment()->getCurrentCheckoutTenant();
        }

        if (!empty($tenant)) {
            return $tenant;
        }

        return 'default';
    }
}
