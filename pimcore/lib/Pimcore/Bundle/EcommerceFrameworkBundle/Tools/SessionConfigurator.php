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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tools;

use Pimcore\Session\SessionConfiguratorInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionConfigurator implements SessionConfiguratorInterface
{
    const ATTRIBUTE_BAG_CART = 'ecommerceframework_cart';
    const ATTRIBUTE_BAG_ENVIRONMENT = 'ecommerceframework_environment';
    const ATTRIBUTE_BAG_PRICING_ENVIRONMENT = 'ecommerceframework_pricing_environment';
    const ATTRIBUTE_BAG_PAYMENT_ENVIRONMENT = 'ecommerceframework_payment_environment';

    /**
     * @return string[]
     */
    protected function getBagNames() {
        return [
            self::ATTRIBUTE_BAG_CART,
            self::ATTRIBUTE_BAG_ENVIRONMENT,
            self::ATTRIBUTE_BAG_PRICING_ENVIRONMENT,
            self::ATTRIBUTE_BAG_PAYMENT_ENVIRONMENT
        ];
    }

    /**
     * @inheritDoc
     */
    public function configure(SessionInterface $session)
    {
        $bagNames = $this->getBagNames();

        foreach ($bagNames as $bagName) {
            $bag = new NamespacedAttributeBag('_' . $bagName);
            $bag->setName($bagName);

            $session->registerBag($bag);
        }
    }

    /**
     * Clears all session bags filled from the e-commerce framework
     *
     * @param SessionInterface $session
     */
    public function clearSession(SessionInterface $session) {
        $bagNames = $this->getBagNames();

        foreach ($bagNames as $bagName) {
            $sessionBag = $session->getBag($bagName);
            if($sessionBag) {
                $sessionBag->clear();
            }
        }
    }
}
