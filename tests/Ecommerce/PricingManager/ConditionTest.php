<?php

namespace Pimcore\Tests\Ecommerce\PricingManager;

use Codeception\Util\Stub;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceCalculator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CartAmount;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CatalogCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CatalogProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\DateRange;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Environment;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\SessionConfigurator;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Tests\Test\EcommerceTestCase;

class ConditionTest extends EcommerceTestCase
{
    public function testCartAmount()
    {
        $cart = $this->getMockBuilder(SessionCart::class)->getMock();

        $priceCalculator = Stub::construct(CartPriceCalculator::class, [$this->buildEnvironment(), $cart], [
            'getSubTotal' => function () {
                return new Price(Decimal::create(200), new Currency('EUR')) ;
            },
        ]);

        $cart->method('getPriceCalculator')->willReturn($priceCalculator);

        /** @var Environment $environment */
        $environment = Stub::make(Environment::class, [
            'getCart' => function () use ($cart) {
                return $cart;
            },

            'getProduct' => function () {
                return null;
            },
        ]);

        $cartAmount = new CartAmount();
        $cartAmount->setLimit(100);

        $this->assertTrue($cartAmount->check($environment), 'check with limit 100 vs. value 200');

        $cartAmount = new CartAmount();
        $cartAmount->setLimit(200);

        $this->assertTrue($cartAmount->check($environment), 'check with limit 200 vs. value 200');

        $cartAmount = new CartAmount();
        $cartAmount->setLimit(300);

        $this->assertFalse($cartAmount->check($environment), 'check with limit 300 vs. value 200');

        /** @var Environment $environment */
        $environment = Stub::make(Environment::class, [
            'getCart' => function () use ($cart) {
                return $cart;
            },

            'getProduct' => function () {
                return 'notnull';
            },
        ]);

        $cartAmount = new CartAmount();
        $cartAmount->setLimit(300);

        $this->assertFalse($cartAmount->check($environment), 'check not empty product');

        /** @var Environment $environment */
        $environment = Stub::make(Environment::class, [
            'getCart' => function () use ($cart) {
                return null;
            },

            'getProduct' => function () {
                return 'notnull';
            },
        ]);

        $cartAmount = new CartAmount();
        $cartAmount->setLimit(300);

        $this->assertFalse($cartAmount->check($environment), 'check not empty product and empty cart');
    }

    /**
     * @param string $path
     *
     * @return AbstractCategory
     */
    private function mockCategory($path)
    {
        $category = $this->getMockBuilder(AbstractCategory::class)->getMock();
        $category->method('getFullPath')->willReturn($path);

        return $category;
    }

    public function testCatalogCategory()
    {
        $environmentCategories = [];

        /** @var Environment $environment */
        $environment = Stub::make(Environment::class, [
            'getCategories' => function () use ($environmentCategories) {
                return $environmentCategories;
            },
        ]);

        $catalogCategory = new CatalogCategory();
        $catalogCategory->setCategories([]);

        $this->assertFalse($catalogCategory->check($environment), 'check empty environment with empty categories');

        $environmentCategories = [];
        $environmentCategories[] = $this->mockCategory('/categories/fashion/shoes');
        $environmentCategories[] = $this->mockCategory('/categories/fashion/tshirts');

        /** @var Environment $environment */
        $environment = Stub::make(Environment::class, [
            'getCategories' => function () use ($environmentCategories) {
                return $environmentCategories;
            },
        ]);

        $catalogCategory = new CatalogCategory();
        $catalogCategory->setCategories([]);

        $this->assertFalse($catalogCategory->check($environment), 'check filled environment with empty categories');

        $allowedCategories = [];
        $allowedCategories[] = $this->mockCategory('/categories/fashion/jeans');
        $allowedCategories[] = $this->mockCategory('/categories/fashion/glasses');

        $catalogCategory = new CatalogCategory();
        $catalogCategory->setCategories($allowedCategories);

        $this->assertFalse($catalogCategory->check($environment), 'check filled environment with other categories');

        $allowedCategories = [];
        $allowedCategories[] = $this->mockCategory('/categories/fashion/jeans');
        $allowedCategories[] = $this->mockCategory('/categories/fashion/shoes');

        $catalogCategory = new CatalogCategory();
        $catalogCategory->setCategories($allowedCategories);

        $this->assertTrue($catalogCategory->check($environment), 'check filled environment with exact categories 1');

        $allowedCategories = [];
        $allowedCategories[] = $this->mockCategory('/categories/fashion/jeans');
        $allowedCategories[] = $this->mockCategory('/categories/fashion/tshirts');

        $catalogCategory = new CatalogCategory();
        $catalogCategory->setCategories($allowedCategories);

        $this->assertTrue($catalogCategory->check($environment), 'check filled environment with exact categories 2');

        $allowedCategories = [];
        $allowedCategories[] = $this->mockCategory('/categories/fashion');
        $allowedCategories[] = $this->mockCategory('/categories/diy');

        $catalogCategory = new CatalogCategory();
        $catalogCategory->setCategories($allowedCategories);

        $this->assertTrue($catalogCategory->check($environment), 'check filled environment with parent category');
    }

    /**
     * @param int $id
     * @param int|null $parentId
     *
     * @return AbstractProduct
     */
    private function mockProduct($id, $parentId = null)
    {
        $product = $this->getMockBuilder(AbstractProduct::class)->getMock();
        $product->method('getId')->willReturn($id);

        if ($parentId) {
            $subProduct = $this->mockProduct($parentId);
            $product->method('getParent')->willReturn($subProduct);
        } else {
            $product->method('getParent')->willReturn(null);
        }

        return $product;
    }

    /**
     * @return CartInterface
     */
    private function mockCart()
    {
        $sessionBag = $this->buildSession()->getBag(SessionConfigurator::ATTRIBUTE_BAG_CART);

        /** @var SessionCart|\PHPUnit_Framework_MockObject_Stub $cart */
        $cart = Stub::construct(SessionCart::class, [], [
            'getSessionBag' => function () use ($sessionBag) {
                return $sessionBag;
            },
            'isCartReadOnly' => function () {
                return false;
            },
            'modified' => function () {
            },
        ]);

        $cart->addItem($this->mockProduct(451, 450), 2);
        $cart->addItem($this->mockProduct(452, 450), 1);
        $cart->addItem($this->mockProduct(356, 350), 6);
        $cart->addItem($this->mockProduct(981), 6);

        return $cart;
    }

    public function testCatalogProduct()
    {
        /** @var Environment $environment */
        $environment = Stub::make(Environment::class, [
            'getExecutionMode' => function () {
                return EnvironmentInterface::EXECUTION_MODE_PRODUCT;
            },
        ]);

        $catalogProduct = new CatalogProduct();
        $catalogProduct->setProducts([]);

        $this->assertFalse($catalogProduct->check($environment), 'check empty environment with empty products');

        $catalogProduct = new CatalogProduct();
        $catalogProduct->setProducts([$this->mockProduct(450), $this->mockProduct(999)]);

        $this->assertFalse($catalogProduct->check($environment), 'check empty environment with filled products');

        $cart = $this->mockCart();

        $environment = Stub::make(Environment::class, [
            'getExecutionMode' => function () {
                return EnvironmentInterface::EXECUTION_MODE_PRODUCT;
            },
            'getCart' => function () use ($cart) {
                return $cart;
            },
        ]);

        $this->assertFalse($catalogProduct->check($environment), 'check environment with cart against filled products');

        $mockProduct1 = $this->mockProduct(1);
        $environment = Stub::make(Environment::class, [
            'getExecutionMode' => function () {
                return EnvironmentInterface::EXECUTION_MODE_PRODUCT;
            },
            'getCart' => function () use ($cart) {
                return $cart;
            },
            'getProduct' => function () use ($mockProduct1) {
                return $mockProduct1;
            },
        ]);

        $this->assertFalse($catalogProduct->check($environment), 'check environment with cart and product against filled products');

        $mockProduct1 = $this->mockProduct(999);
        $environment = Stub::make(Environment::class, [
            'getExecutionMode' => function () {
                return EnvironmentInterface::EXECUTION_MODE_PRODUCT;
            },
            'getCart' => function () use ($cart) {
                return $cart;
            },
            'getProduct' => function () use ($mockProduct1) {
                return $mockProduct1;
            },
        ]);

        $this->assertTrue($catalogProduct->check($environment), 'check environment with cart and product against filled products');

        $mockProduct1 = $this->mockProduct(1);
        $environment = Stub::make(Environment::class, [
            'getExecutionMode' => function () {
                return EnvironmentInterface::EXECUTION_MODE_CART;
            },
            'getCart' => function () use ($cart) {
                return $cart;
            },
            'getProduct' => function () use ($mockProduct1) {
                return $mockProduct1;
            },
        ]);

        $this->assertTrue($catalogProduct->check($environment), 'check environment with cart and product against filled products');

        $catalogProduct = new CatalogProduct();
        $catalogProduct->setProducts([$this->mockProduct(888), $this->mockProduct(999)]);

        $mockProduct1 = $this->mockProduct(1);
        $environment = Stub::make(Environment::class, [
            'getExecutionMode' => function () {
                return EnvironmentInterface::EXECUTION_MODE_CART;
            },
            'getCart' => function () use ($cart) {
                return $cart;
            },
            'getProduct' => function () use ($mockProduct1) {
                return $mockProduct1;
            },
        ]);

        $this->assertFalse($catalogProduct->check($environment), 'check environment with cart and product against filled products');
    }

    public function testDateRange()
    {
        /** @var Environment $environment */
        $environment = Stub::make(Environment::class, [
        ]);

        $dateRange = new DateRange();
        $this->assertFalse($dateRange->check($environment), 'check empty daterange');

        $dateRange = new DateRange();
        $dateRange->setStarting(new \DateTime('-1 day'));
        $dateRange->setEnding(new \DateTime('+1 day'));
        $this->assertTrue($dateRange->check($environment), 'check valid daterange');

        $dateRange = new DateRange();
        $dateRange->setStarting(new \DateTime('-2 day'));
        $dateRange->setEnding(new \DateTime('-1 day'));
        $this->assertFalse($dateRange->check($environment), 'check in-valid daterange');

        $dateRange = new DateRange();
        $dateRange->setStarting(new \DateTime('+1 day'));
        $dateRange->setEnding(new \DateTime('+2 day'));
        $this->assertFalse($dateRange->check($environment), 'check in-valid daterange');

        $dateRange = new DateRange();
        $dateRange->setStarting(new \DateTime('+1 day'));
        $dateRange->setEnding(new \DateTime('-1 day'));
        $this->assertFalse($dateRange->check($environment), 'check in-valid daterange');
    }
}
