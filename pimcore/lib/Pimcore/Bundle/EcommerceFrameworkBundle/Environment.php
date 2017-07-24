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
use Pimcore\Service\Locale;
use Pimcore\Tool;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Environment implements IEnvironment
{
    const USER_ID_NOT_SET = -1;

    /**
     * @var Locale
     */
    protected $localeService;

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

    public function __construct(Locale $localeService, array $options = [])
    {
        $this->localeService = $localeService;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));
    }

    protected function processOptions(array $options)
    {
        $this->defaultCurrency = new Currency((string)$options['defaultCurrency']);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['defaultCurrency']);
        $resolver->setAllowedTypes('defaultCurrency', 'string');
        $resolver->setDefaults(['defaultCurrency' => 'EUR']);
    }

    protected function load()
    {
    }

    public function save()
    {
    }

    public function getAllCustomItems()
    {
        $this->load();

        return $this->customItems;
    }

    public function getCustomItem($key, $defaultValue = null)
    {
        $this->load();

        if (isset($this->customItems[$key])) {
            return $this->customItems[$key];
        }

        return $defaultValue;
    }

    public function setCustomItem($key, $value)
    {
        $this->load();

        $this->customItems[$key] = $value;
    }

    /**
     * @return int
     */
    public function getCurrentUserId()
    {
        $this->load();

        return $this->userId;
    }

    /**
     * @param int $userId
     *
     * @return $this
     */
    public function setCurrentUserId($userId)
    {
        $this->load();

        $this->userId = (int)$userId;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasCurrentUserId()
    {
        $this->load();

        return $this->getCurrentUserId() !== self::USER_ID_NOT_SET;
    }

    public function removeCustomItem($key)
    {
        $this->load();

        unset($this->customItems[$key]);
    }

    public function clearEnvironment()
    {
        $this->load();

        $this->customItems = null;
        $this->userId = null;
        $this->currentAssortmentTenant = null;
        $this->currentAssortmentSubTenant = null;
        $this->currentCheckoutTenant = null;
        $this->currentTransientCheckoutTenant = null;
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
        $this->load();

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
        $this->load();

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
        $this->load();

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
        $this->load();

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
        $this->load();

        return $this->useGuestCart;
    }

    /**
     * @param bool $useGuestCart
     */
    public function setUseGuestCart($useGuestCart)
    {
        $this->load();

        $this->useGuestCart = (bool)$useGuestCart;
    }

    /**
     * sets current assortment tenant which is used for indexing and product lists
     *
     * @param $tenant string
     */
    public function setCurrentAssortmentTenant($tenant)
    {
        $this->load();

        $this->currentAssortmentTenant = $tenant;
    }

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentTenant()
    {
        $this->load();

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
        $this->load();

        $this->currentAssortmentSubTenant = $subTenant;
    }

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return mixed
     */
    public function getCurrentAssortmentSubTenant()
    {
        $this->load();

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
        $this->load();

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
        $this->load();

        return $this->currentTransientCheckoutTenant;
    }

    /**
     * gets current system locale
     *
     * @return null|string
     */
    public function getSystemLocale()
    {
        $locale = $this->localeService->findLocale();
        if (Tool::isValidLanguage($locale)) {
            return (string)$locale;
        }

        return Tool::getDefaultLanguage();
    }
}
