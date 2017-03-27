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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle;

use Pimcore\Bundle\PimcoreBundle\Service\Locale;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tools\SessionConfigurator;
use Pimcore\Cache\Runtime;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Environment implements IEnvironment
{
    const SESSION_KEY_CUSTOM_ITEMS = "customitems";
    const SESSION_KEY_USERID = "userid";
    const SESSION_KEY_USE_GUEST_CART = "useguestcart";
    const SESSION_KEY_ASSORTMENT_TENANT = "currentassortmenttenant";
    const SESSION_KEY_ASSORTMENT_SUB_TENANT = "currentassortmentsubtenant";
    const SESSION_KEY_CHECKOUT_TENANT = "currentcheckouttenant";
    const USER_ID_NOT_SET = -1;

    /**
     * @var AttributeBagInterface
     */
    protected $session;

    protected $customItems = [];

    /**
     * @var int
     */
    protected $userId = self::USER_ID_NOT_SET;

    /**
     * @var bool
     */
    protected $useGuestCart = false;


    protected $currentAssortmentTenant = null;
    protected $currentAssortmentSubTenant = null;
    protected $currentCheckoutTenant = null;

    /**
     * @var Currency
     */
    protected $defaultCurrency = null;

    /**
     * locale set on container
     *
     * @var Locale
     */
    protected $localeService = null;

    /**
     * @var SessionInterface
     */
    protected $containerSession = null;

    /**
     * current transient checkout tenant
     * this value will not be stored into the session and is only valid for current process
     * set with setCurrentCheckoutTenant('tenant', false');
     *
     * @var string
     */
    protected $currentTransientCheckoutTenant = null;

    public function __construct($config, SessionInterface $containerSession, Locale $localeService)
    {
        $this->localeService = $localeService;
        $this->containerSession = $containerSession;

        $this->loadFromSession();


        $this->defaultCurrency = new Currency((string)$config->defaultCurrency);
    }

    protected function loadFromSession()
    {
        if (php_sapi_name() != "cli") {
            $this->session = $this->buildSession();

            $this->customItems = $this->session->get(self::SESSION_KEY_CUSTOM_ITEMS);
            if ($this->customItems==null) {
                $this->customItems=[];
            }

            $this->userId = $this->session->get(self::SESSION_KEY_USERID);

            $this->currentAssortmentTenant = $this->session->get(self::SESSION_KEY_ASSORTMENT_TENANT);

            $this->currentAssortmentSubTenant = $this->session->get(self::SESSION_KEY_ASSORTMENT_SUB_TENANT);

            $this->currentCheckoutTenant = $this->session->get(self::SESSION_KEY_CHECKOUT_TENANT);
            $this->currentTransientCheckoutTenant = $this->session->get(self::SESSION_KEY_CHECKOUT_TENANT);

            $this->useGuestCart = $this->session->get(self::SESSION_KEY_USE_GUEST_CART);
        }
    }

    public function save()
    {
        if (php_sapi_name() != "cli") {
            $this->session->set(self::SESSION_KEY_CUSTOM_ITEMS, $this->customItems);

            $this->session->set(self::SESSION_KEY_USERID, $this->userId);

            $this->session->set(self::SESSION_KEY_ASSORTMENT_TENANT, $this->currentAssortmentTenant);

            $this->session->set(self::SESSION_KEY_ASSORTMENT_SUB_TENANT, $this->currentAssortmentSubTenant);

            $this->session->set(self::SESSION_KEY_CHECKOUT_TENANT, $this->currentCheckoutTenant);

            $this->session->set(self::SESSION_KEY_USE_GUEST_CART, $this->useGuestCart);
        }
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
        $key = self::SESSION_KEY_CUSTOM_ITEMS;
        $this->session->remove($key);
        $this->customItems = null;

        $key = self::SESSION_KEY_USERID;
        $this->session->remove($key);
        $this->userId = null;

        $key = self::SESSION_KEY_ASSORTMENT_TENANT;
        $this->session->remove($key);
        $this->currentAssortmentTenant = null;

        $key = self::SESSION_KEY_ASSORTMENT_SUB_TENANT;
        $this->session->remove($key);
        $this->currentAssortmentSubTenant = null;

        $key = self::SESSION_KEY_CHECKOUT_TENANT;
        $this->session->remove($key);
        $this->currentCheckoutTenant = null;
        $this->currentTransientCheckoutTenant = null;

        $key = self::SESSION_KEY_USE_GUEST_CART;
        $this->session->remove($key);
        $this->useGuestCart = false;
    }

    /**
     * @deprecated
     *
     * use setCurrentAssortmentTenant instead
     *
     * @param string $currentTenant
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
     * @return boolean
     */
    public function getUseGuestCart()
    {
        return $this->useGuestCart;
    }

    /**
     * @param boolean $useGuestCart
     */
    public function setUseGuestCart($useGuestCart)
    {
        $this->useGuestCart = (bool)$useGuestCart;
    }

    /**
     * sets current assortment tenant which is used for indexing and product lists
     *
     * @param $tenant string
     * @return mixed
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
    protected function buildSession()
    {
        return $this->containerSession->getBag(SessionConfigurator::ATTRIBUTE_BAG_ENVIRONMENT);
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
            $locale = null;

            if (Runtime::isRegistered('model.locale')) {
                $locale = Runtime::get('model.locale');
            }

            if (null === $locale) {
                $locale = $this->localeService->findLocale();
            }

            if (Tool::isValidLanguage($locale)) {
                return (string) $locale;
            }
            throw new \Exception("Not supported language");
        } catch (\Exception $e) {
            return Tool::getDefaultLanguage();
        }
    }
}
