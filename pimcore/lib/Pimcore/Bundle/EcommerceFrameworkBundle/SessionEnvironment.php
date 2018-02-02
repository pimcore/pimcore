<?php

declare(strict_types=1);

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

use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\SessionConfigurator;
use Pimcore\Localization\Locale;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionEnvironment extends Environment implements IEnvironment
{
    const SESSION_KEY_CUSTOM_ITEMS = 'customitems';
    const SESSION_KEY_USERID = 'userid';
    const SESSION_KEY_USE_GUEST_CART = 'useguestcart';
    const SESSION_KEY_ASSORTMENT_TENANT = 'currentassortmenttenant';
    const SESSION_KEY_ASSORTMENT_SUB_TENANT = 'currentassortmentsubtenant';
    const SESSION_KEY_CHECKOUT_TENANT = 'currentcheckouttenant';

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var bool
     */
    protected $sessionLoaded = false;

    public function __construct(SessionInterface $session, Locale $localeService, array $options = [])
    {
        parent::__construct($localeService, $options);

        $this->session = $session;
    }

    protected function load()
    {
        if ($this->sessionLoaded) {
            return;
        }

        //if the session was not explicitly started in cli environment, do nothing
        if ('cli' === php_sapi_name() && !$this->session->isStarted()) {
            return;
        }

        $sessionBag = $this->getSessionBag();

        $this->customItems = $sessionBag->get(self::SESSION_KEY_CUSTOM_ITEMS, []);

        $this->userId = $sessionBag->get(self::SESSION_KEY_USERID);

        $this->currentAssortmentTenant = $sessionBag->get(self::SESSION_KEY_ASSORTMENT_TENANT);
        $this->currentAssortmentSubTenant = $sessionBag->get(self::SESSION_KEY_ASSORTMENT_SUB_TENANT);

        $this->currentCheckoutTenant = $sessionBag->get(self::SESSION_KEY_CHECKOUT_TENANT);
        $this->currentTransientCheckoutTenant = $sessionBag->get(self::SESSION_KEY_CHECKOUT_TENANT);

        $this->useGuestCart = $sessionBag->get(self::SESSION_KEY_USE_GUEST_CART);

        $this->sessionLoaded = true;
    }

    public function save()
    {
        //if the session was not explicitly started in cli environment, do nothing
        if ('cli' === php_sapi_name() && !$this->session->isStarted()) {
            return;
        }

        $this->load();

        $sessionBag = $this->getSessionBag();
        $sessionBag->set(self::SESSION_KEY_CUSTOM_ITEMS, $this->customItems);
        $sessionBag->set(self::SESSION_KEY_USERID, $this->userId);
        $sessionBag->set(self::SESSION_KEY_ASSORTMENT_TENANT, $this->currentAssortmentTenant);
        $sessionBag->set(self::SESSION_KEY_ASSORTMENT_SUB_TENANT, $this->currentAssortmentSubTenant);
        $sessionBag->set(self::SESSION_KEY_CHECKOUT_TENANT, $this->currentCheckoutTenant);
        $sessionBag->set(self::SESSION_KEY_USE_GUEST_CART, $this->useGuestCart);
    }

    public function clearEnvironment()
    {
        parent::clearEnvironment();

        //if the session was not explicitly started in cli environment, do nothing
        if ('cli' === php_sapi_name() && !$this->session->isStarted()) {
            return;
        }

        $this->load();

        $sessionBag = $this->getSessionBag();

        $sessionBag->remove(self::SESSION_KEY_CUSTOM_ITEMS);
        $sessionBag->remove(self::SESSION_KEY_USERID);
        $sessionBag->remove(self::SESSION_KEY_USE_GUEST_CART);
        $sessionBag->remove(self::SESSION_KEY_ASSORTMENT_TENANT);
        $sessionBag->remove(self::SESSION_KEY_ASSORTMENT_SUB_TENANT);
        $sessionBag->remove(self::SESSION_KEY_CHECKOUT_TENANT);
    }

    /**
     * @return AttributeBagInterface
     */
    protected function getSessionBag(): AttributeBagInterface
    {
        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $this->session->getBag(SessionConfigurator::ATTRIBUTE_BAG_ENVIRONMENT);

        return $sessionBag;
    }
}
