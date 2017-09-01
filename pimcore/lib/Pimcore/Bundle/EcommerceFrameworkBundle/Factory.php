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
use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailabilitySystemLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartManagerLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManagerFactoryLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICommitOrderProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICommitOrderProcessorLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\PimcoreEcommerceFrameworkExtension;
use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterService;
use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\IFilterServiceLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\IndexService;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType;
use Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\IService;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManagerLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IPaymentManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystemLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPricingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITrackingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\IVoucherService;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ITokenManager;
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
     * @var ICartManagerLocator
     */
    private $cartManagers;

    /**
     * Tenant specific order managers
     *
     * @var IOrderManagerLocator
     */
    private $orderManagers;

    /**
     * Price systems registered by name
     *
     * @var IPriceSystemLocator
     */
    private $priceSystems;

    /**
     * Availability systems registered by name
     *
     * @var IAvailabilitySystemLocator
     */
    private $availabilitySystems;

    /**
     * Checkout manager factories registered by tenant
     *
     * @var ICheckoutManagerFactoryLocator
     */
    private $checkoutManagerFactories;

    /**
     * Commit order processors registered by tenant
     *
     * @var ICommitOrderProcessorLocator
     */
    private $commitOrderProcessors;

    /**
     * Filter services registered by ^tenant
     *
     * @var IFilterServiceLocator
     */
    private $filterServices;

    /**
     * Systems with multiple instances (e.g. price systems or tenant specific systems) are
     * injected through a service locator which is indexed by tenant/name. All other services
     * are loaded from the container on demand to make sure only services needed are built.
     *
     * @param ContainerInterface $container
     * @param ICartManagerLocator $cartManagers
     * @param IOrderManagerLocator $orderManagers
     * @param IPriceSystemLocator $priceSystems
     * @param IAvailabilitySystemLocator $availabilitySystems
     * @param ICheckoutManagerFactoryLocator $checkoutManagerFactories
     * @param ICommitOrderProcessorLocator $commitOrderProcessors
     * @param IFilterServiceLocator $filterServices
     */
    public function __construct(
        ContainerInterface $container,
        ICartManagerLocator $cartManagers,
        IOrderManagerLocator $orderManagers,
        IPriceSystemLocator $priceSystems,
        IAvailabilitySystemLocator $availabilitySystems,
        ICheckoutManagerFactoryLocator $checkoutManagerFactories,
        ICommitOrderProcessorLocator $commitOrderProcessors,
        IFilterServiceLocator $filterServices
    ) {
        $this->container                = $container;
        $this->cartManagers             = $cartManagers;
        $this->orderManagers            = $orderManagers;
        $this->priceSystems             = $priceSystems;
        $this->availabilitySystems      = $availabilitySystems;
        $this->checkoutManagerFactories = $checkoutManagerFactories;
        $this->commitOrderProcessors    = $commitOrderProcessors;
        $this->filterServices           = $filterServices;
    }

    public static function getInstance(): self
    {
        return \Pimcore::getContainer()->get(PimcoreEcommerceFrameworkExtension::SERVICE_ID_FACTORY);
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
     */
    public function getCartManager(string $tenant = null): ICartManager
    {
        return $this->cartManagers->getCartManager($tenant);
    }

    /**
     * Returns order manager for a specific tenant. If no tenant is passed it will fall back to the current
     * checkout tenant or to "default" if no current checkout tenant is set.
     *
     * @param string|null $tenant
     *
     * @return IOrderManager
     */
    public function getOrderManager(string $tenant = null): IOrderManager
    {
        return $this->orderManagers->getOrderManager($tenant);
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
     */
    public function getPriceSystem(string $name = null): IPriceSystem
    {
        return $this->priceSystems->getPriceSystem($name);
    }

    /**
     * Returns an availability system by name. Falls back to "default" if no name is passed.
     *
     * @param string|null $name
     *
     * @return IAvailabilitySystem
     */
    public function getAvailabilitySystem(string $name = null): IAvailabilitySystem
    {
        return $this->availabilitySystems->getAvailabilitySystem($name);
    }

    /**
     * Returns checkout manager for a specific tenant. If no tenant is passed it will fall back to the current
     * checkout tenant or to "default" if no current checkout tenant is set.
     *
     * @param ICart $cart
     * @param string|null $tenant
     *
     * @return ICheckoutManager
     */
    public function getCheckoutManager(ICart $cart, string $tenant = null): ICheckoutManager
    {
        $factory = $this->checkoutManagerFactories->getCheckoutManagerFactory($tenant);

        return $factory->createCheckoutManager($cart);
    }

    /**
     * Returns a commit order processor which is configured for a specific checkout manager
     *
     * @param string|null $tenant
     *
     * @return ICommitOrderProcessor
     */
    public function getCommitOrderProcessor(string $tenant = null): ICommitOrderProcessor
    {
        return $this->commitOrderProcessors->getCommitOrderProcessor($tenant);
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
     */
    public function getFilterService(string $tenant = null): FilterService
    {
        return $this->filterServices->getFilterService($tenant);
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
}
