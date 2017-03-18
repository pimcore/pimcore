<?php
/**
 * Created by PhpStorm.
 * User: cfasching
 * Date: 18.03.2017
 * Time: 16:34
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tools;


use Pimcore\Bundle\PimcoreBundle\Session\SessionConfiguratorInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionConfigurator implements SessionConfiguratorInterface
{
    const ATTRIBUTE_BAG_CART = "ecommerceframework_cart";
    const ATTRIBUTE_BAG_ENVIRONMENT = "ecommerceframework_environment";
    const ATTRIBUTE_BAG_PRICING_ENVIRONMENT = "ecommerceframework_pricing_environment";

    /**
     * @inheritDoc
     */
    public function configure(SessionInterface $session)
    {
        $bag = new NamespacedAttributeBag('_' . self::ATTRIBUTE_BAG_CART);
        $bag->setName(self::ATTRIBUTE_BAG_CART);
        $session->registerBag($bag);

        $bag = new NamespacedAttributeBag('_' . self::ATTRIBUTE_BAG_ENVIRONMENT);
        $bag->setName(self::ATTRIBUTE_BAG_ENVIRONMENT);
        $session->registerBag($bag);

        $bag = new NamespacedAttributeBag('_' . self::ATTRIBUTE_BAG_PRICING_ENVIRONMENT);
        $bag->setName(self::ATTRIBUTE_BAG_PRICING_ENVIRONMENT);
        $session->registerBag($bag);
    }
}