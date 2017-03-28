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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tools;

use Pimcore\Session\SessionConfiguratorInterface;
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
