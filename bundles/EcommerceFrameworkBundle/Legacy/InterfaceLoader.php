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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Legacy;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystemLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartFactoryInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceCalculatorFactoryInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceCalculatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManagerFactoryInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManagerFactoryLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessorLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\ComponentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterServiceLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\FactFinderConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\FindologicConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\MockupConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\MysqlConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\ExtendedGetterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\GetterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\InterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\RelationInterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\BatchProcessingWorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ProductInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\ServiceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentFactoryInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListFilterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\PaymentManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\CachingPriceSystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\DiscountInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\GiftInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\ProductDiscountInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ActionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\BracketInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CartAmountInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CartProductInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CatalogProductInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CategoryInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\DateRangeInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CartProductActionAddInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CartProductActionRemoveInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CartUpdateInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CategoryPageViewInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CheckoutCompleteInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CheckoutInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\CheckoutStepInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductImpressionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ProductViewInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilderInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ExportableTokenManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\TokenManagerFactoryInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\TokenManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\VoucherServiceInterface;

class InterfaceLoader
{
    protected static $interfaces = [
        ComponentInterface::class,
        EnvironmentInterface::class,
        AvailabilityInterface::class,
        AvailabilitySystemInterface::class,
        AvailabilitySystemLocatorInterface::class,
        CartFactoryInterface::class,
        CartInterface::class,
        CartItemInterface::class,
        CartManagerInterface::class,
        CartManagerLocatorInterface::class,
        CartPriceCalculatorFactoryInterface::class,
        CartPriceCalculatorInterface::class,
        CheckoutManagerFactoryInterface::class,
        CheckoutManagerFactoryLocatorInterface::class,
        CheckoutManagerInterface::class,
        CheckoutStepInterface::class,
        CommitOrderProcessorInterface::class,
        CommitOrderProcessorLocatorInterface::class,
        FilterServiceLocatorInterface::class,
        ConfigInterface::class,
        ElasticSearchConfigInterface::class,
        FactFinderConfigInterface::class,
        FindologicConfigInterface::class,
        MockupConfigInterface::class,
        MysqlConfigInterface::class,
        ExtendedGetterInterface::class,
        GetterInterface::class,
        InterpreterInterface::class,
        RelationInterpreterInterface::class,
        ProductListInterface::class,
        BatchProcessingWorkerInterface::class,
        WorkerInterface::class,
        CheckoutableInterface::class,
        IndexableInterface::class,
        ProductInterface::class,
        ServiceInterface::class,
        OrderAgentFactoryInterface::class,
        OrderAgentInterface::class,
        OrderListFilterInterface::class,
        OrderListInterface::class,
        OrderListItemInterface::class,
        OrderManagerInterface::class,
        OrderManagerLocatorInterface::class,
        PaymentManagerInterface::class,
        StatusInterface::class,
        PaymentInterface::class,
        CachingPriceSystemInterface::class,
        ModificatedPriceInterface::class,
        PriceInfoInterface::class,
        PriceInterface::class,
        PriceSystemInterface::class,
        PriceSystemLocatorInterface::class,
        ActionInterface::class,
        ConditionInterface::class,
        EnvironmentInterface::class,
        \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PriceInfoInterface::class,
        PricingManagerInterface::class,
        PricingManagerLocatorInterface::class,
        RuleInterface::class,
        DiscountInterface::class,
        GiftInterface::class,
        ProductDiscountInterface::class,
        BracketInterface::class,
        CartAmountInterface::class,
        CartProductInterface::class,
        CatalogProductInterface::class,
        CategoryInterface::class,
        DateRangeInterface::class,
        CartProductActionAddInterface::class,
        CartProductActionRemoveInterface::class,
        CartUpdateInterface::class,
        CategoryPageViewInterface::class,
        CheckoutCompleteInterface::class,
        CheckoutInterface::class,
        CheckoutStepInterface::class,
        ProductImpressionInterface::class,
        ProductViewInterface::class,
        TrackerInterface::class,
        TrackingItemBuilderInterface::class,
        TrackingManagerInterface::class,
        VoucherServiceInterface::class,
        ExportableTokenManagerInterface::class,
        TokenManagerFactoryInterface::class,
        TokenManagerInterface::class,
    ];

    /**
     * @deprecated
     * class interface_exists on all interfaces of e-commerce framework
     * this is necessary to make sure that interfaces are loaded during compile time and
     * no error is thrown during container compile when using old interfaces (e.g. IProduct) in
     * custom service implementations.
     *
     * should be removed when BC layer for interfaces is removed
     */
    public static function loadInterfaces()
    {
        foreach (self::$interfaces as $interface) {
            interface_exists($interface);
        }
    }
}
