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

/**
 * Interface for environment implementations of online shop framework
 */
interface EnvironmentInterface extends ComponentInterface
{
    /**
     * Returns current user id
     *
     * @return int
     */
    public function getCurrentUserId();

    /**
     * Sets current user id
     *
     * @param int $userId
     *
     * @return void
     */
    public function setCurrentUserId($userId);

    /**
     * Checks if a user id is set
     *
     * @return bool
     */
    public function hasCurrentUserId();

    /**
     * Sets custom item to environment - which is saved to the session then
     * save()-call is needed to save the custom items
     *
     * @param string $key
     * @param mixed $value
     */
    public function setCustomItem($key, $value);

    /**
     * Removes custom item from the environment
     * save()-call is needed to save the custom items
     *
     * @param string $key
     */
    public function removeCustomItem($key);

    /**
     * Returns custom saved item from environment
     *
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getCustomItem($key, $defaultValue = null);

    /**
     * Returns all custom items from environment
     *
     * @return array
     */
    public function getAllCustomItems();

    /**
     * Resets environment
     * save()-call is needed to save changes
     */
    public function clearEnvironment();

    /**
     * Sets current assortment tenant which is used for indexing and product lists
     *
     * @param string $tenant
     */
    public function setCurrentAssortmentTenant($tenant);

    /**
     * Returns current assortment tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentTenant();

    /**
     * Sets current assortment sub tenant which is used for indexing and product lists
     *
     * TODO: is this mixed or string?
     *
     * @param mixed $subTenant
     */
    public function setCurrentAssortmentSubTenant($subTenant);

    /**
     * Returns current sub assortment tenant which is used for indexing and product lists
     *
     * @return mixed
     */
    public function getCurrentAssortmentSubTenant();

    /**
     * Sets current checkout tenant which is used for cart and checkout manager
     *
     * @param string $tenant
     * @param bool $persistent - if set to false, tenant is not stored to session and only valid for current process
     */
    public function setCurrentCheckoutTenant($tenant, $persistent = true);

    /**
     * Returns current assortment tenant which is used for cart and checkout manager
     *
     * @return string
     */
    public function getCurrentCheckoutTenant();

    /**
     * Set the default currency in a multi-currency environment.
     *
     * @param Currency $currency
     */
    public function setDefaultCurrency(Currency $currency);

    /**
     * Returns instance of default currency
     *
     * @return Currency
     */
    public function getDefaultCurrency();

    /**
     * @return bool
     */
    public function getUseGuestCart();

    /**
     * @param bool $useGuestCart
     */
    public function setUseGuestCart($useGuestCart);

    /**
     * Returns current system locale
     *
     * @return null|string
     */
    public function getSystemLocale();

    /**
     * ===========================================
     *
     *  deprecated functions
     *
     * ===========================================
     */

    /**
     * @deprecated use setCurrentAssortmentTenant instead
     *
     * @param string $tenant
     *
     * @return mixed
     */
    public function setCurrentTenant($tenant);

    /**
     * @deprecated use getCurrentAssortmentTenant instead
     *
     * @return string
     */
    public function getCurrentTenant();

    /**
     * @deprecated use setCurrentAssortmentSubTenant instead
     *
     * @param mixed $tenant
     *
     * @return mixed
     */
    public function setCurrentSubTenant($tenant);

    /**
     * @deprecated use getCurrentAssortmentSubTenant instead
     *
     * @return mixed
     */
    public function getCurrentSubTenant();
}

class_alias(EnvironmentInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment');
