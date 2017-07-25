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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\SessionConfigurator;
use Pimcore\Service\Locale;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Environment implements IEnvironment
{
    const SESSION_KEY_CUSTOM_ITEMS = 'customitems';
    const SESSION_KEY_USERID = 'userid';
    const SESSION_KEY_USE_GUEST_CART = 'useguestcart';
    const SESSION_KEY_ASSORTMENT_TENANT = 'currentassortmenttenant';
    const SESSION_KEY_ASSORTMENT_SUB_TENANT = 'currentassortmentsubtenant';
    const SESSION_KEY_CHECKOUT_TENANT = 'currentcheckouttenant';
    const USER_ID_NOT_SET = -1;

    /**
     * @var Locale
     */
    protected $localeService;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var Currency
     */
    protected $defaultCurrency;

    /**
     * @var array
     */
    protected $customItems = [];

    /**
     * @var int
     */
    protected $userId = self::USER_ID_NOT_SET;

    /**
     * @var bool
     */
    protected $useGuestCart = false;

    /**
     * @var string
     */
    protected $currentAssortmentTenant;

    /**
     * @var string
     */
    protected $currentAssortmentSubTenant;

    /**
     * @var string
     */
    protected $currentCheckoutTenant;

    /**
     * Current transient checkout tenant
     *
     * This value will not be stored into the session and is only valid for current process
     * set with setCurrentCheckoutTenant('tenant', false');
     *
     * @var string
     */
    protected $currentTransientCheckoutTenant;

    public function __construct($config, SessionInterface $session, Locale $localeService)
    {
        $this->session       = $session;
        $this->localeService = $localeService;

        $this->loadFromSession();

        $this->defaultCurrency = new Currency((string)$config->defaultCurrency);
    }

    protected function loadFromSession()
    {
        if ('cli' === php_sapi_name()) {
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
    }

    public function save()
    {
        if ('cli' === php_sapi_name()) {
            return;
        }

        $sessionBag = $this->getSessionBag();
        $sessionBag->set(self::SESSION_KEY_CUSTOM_ITEMS, $this->customItems);
        $sessionBag->set(self::SESSION_KEY_USERID, $this->userId);
        $sessionBag->set(self::SESSION_KEY_ASSORTMENT_TENANT, $this->currentAssortmentTenant);
        $sessionBag->set(self::SESSION_KEY_ASSORTMENT_SUB_TENANT, $this->currentAssortmentSubTenant);
        $sessionBag->set(self::SESSION_KEY_CHECKOUT_TENANT, $this->currentCheckoutTenant);
        $sessionBag->set(self::SESSION_KEY_USE_GUEST_CART, $this->useGuestCart);
    }

    public function getAllCustomItems()
    {
        return $this->customItems;
    }

    public function getCustomItem($key)
    {
        return $this->customItems[$key];
    }

    public function setCustomItem($key, $value)
    {
        $this->customItems[$key] = $value;
    }

    /**
     * @return int
     */
    public function getCurrentUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     *
     * @return $this
     */
    public function setCurrentUserId($userId)
    {
        $this->userId = (int)$userId;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasCurrentUserId()
    {
        return $this->getCurrentUserId() !== self::USER_ID_NOT_SET;
    }

    public function removeCustomItem($key)
    {
        unset($this->customItems[$key]);
    }

    public function clearEnvironment()
    {
        $sessionBag = $this->getSessionBag();

        $key = self::SESSION_KEY_CUSTOM_ITEMS;
        $sessionBag->remove($key);
        $this->customItems = null;

        $key = self::SESSION_KEY_USERID;
        $sessionBag->remove($key);
        $this->userId = null;

        $key = self::SESSION_KEY_ASSORTMENT_TENANT;
        $sessionBag->remove($key);
        $this->currentAssortmentTenant = null;

        $key = self::SESSION_KEY_ASSORTMENT_SUB_TENANT;
        $sessionBag->remove($key);
        $this->currentAssortmentSubTenant = null;

        $key = self::SESSION_KEY_CHECKOUT_TENANT;
        $sessionBag->remove($key);
        $this->currentCheckoutTenant = null;
        $this->currentTransientCheckoutTenant = null;

        $key = self::SESSION_KEY_USE_GUEST_CART;
        $sessionBag->remove($key);
        $this->useGuestCart = false;
    }

    /**
     * @deprecated
     *
     * use setCurrentAssortmentTenant instead
     *
     * @param string $currentTenant
     *
     * @return mixed|void
     */
    public function setCurrentTenant($currentTenant)
    {
        $this->setCurrentAssortmentTenant($currentTenant);
    }

    /**
     * @deprecated
     *
     * use getCurrentAssortmentTenant instead
     *
     * @return string
     */
    public function getCurrentTenant()
    {
        return $this->getCurrentAssortmentTenant();
    }

    /**
     * @deprecated
     *
     * use setCurrentAssortmentSubTenant instead
     *
     * @param mixed $currentSubTenant
     *
     * @return mixed|void
     */
    public function setCurrentSubTenant($currentSubTenant)
    {
        $this->setCurrentAssortmentSubTenant($currentSubTenant);
    }

    /**
     * @deprecated
     *
     * use getCurrentAssortmentSubTenant instead
     *
     * @return mixed
     */
    public function getCurrentSubTenant()
    {
        return $this->getCurrentAssortmentSubTenant();
    }

    /**
     * @return Currency
     */
    public function getDefaultCurrency()
    {
        return $this->defaultCurrency;
    }

    /**
     * @return bool
     */
    public function getUseGuestCart()
    {
        return $this->useGuestCart;
    }

    /**
     * @param bool $useGuestCart
     */
    public function setUseGuestCart($useGuestCart)
    {
        $this->useGuestCart = (bool)$useGuestCart;
    }

    /**
     * sets current assortment tenant which is used for indexing and product lists
     *
     * @param $tenant string
     */
    public function setCurrentAssortmentTenant($tenant)
    {
        $this->currentAssortmentTenant = $tenant;
    }

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentTenant()
    {
        return $this->currentAssortmentTenant;
    }

    /**
     * sets current assortment sub tenant which is used for indexing and product lists
     *
     * @param $subTenant mixed
     *
     * @return mixed
     */
    public function setCurrentAssortmentSubTenant($subTenant)
    {
        $this->currentAssortmentSubTenant = $subTenant;
    }

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return mixed
     */
    public function getCurrentAssortmentSubTenant()
    {
        return $this->currentAssortmentSubTenant;
    }

    /**
     * sets current checkout tenant which is used for cart and checkout manager
     *
     * @param string $tenant
     * @param bool $persistent - if set to false, tenant is not stored to session and only valid for current process
     *
     * @return mixed
     */
    public function setCurrentCheckoutTenant($tenant, $persistent = true)
    {
        if ($this->currentCheckoutTenant != $tenant) {
            if ($persistent) {
                $this->currentCheckoutTenant = $tenant;
            }
            $this->currentTransientCheckoutTenant = $tenant;

            Factory::resetInstance();
        }
    }

    /**
     * gets current assortment tenant which is used for cart and checkout manager
     *
     * @return string
     */
    public function getCurrentCheckoutTenant()
    {
        return $this->currentTransientCheckoutTenant;
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

    /**
     * gets current system locale
     *
     * @return null|string
     */
    public function getSystemLocale()
    {
        // try to get the language from the service container
        try {
            $locale = $this->localeService->findLocale();

            if (Tool::isValidLanguage($locale)) {
                return (string) $locale;
            }

            throw new \Exception('Not supported language');
        } catch (\Exception $e) {
            return Tool::getDefaultLanguage();
        }
    }
}
