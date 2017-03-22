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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\Currency;

/**
 * Interface for environment implementations of online shop framework
 */
interface IEnvironment extends IComponent {

    /**
     * returns current user id
     *
     * @abstract
     * @return int
     */
    public function getCurrentUserId();

    /**
     * sets current user id
     *
     * @param int $userId
     * @return void
     */
    public function setCurrentUserId($userId);

    /**
     * check if a user id is set
     *
     * @return bool
     */
    public function hasCurrentUserId();

    /**
     * sets custom item to environment - which is saved to the session then
     * save()-call is needed to save the custom items
     *
     * @abstract
     * @param $key
     * @param $value
     * @return void
     */
    public function setCustomItem($key, $value);

    /**
     * removes custom item from the environment
     * save()-call is needed to save the custom items
     *
     * @abstract
     * @param $key
     * @return mixed
     */
    public function removeCustomItem($key);

    /**
     * returns custom saved item from environment
     *
     * @abstract
     * @param $key
     * @return mixed
     */
    public function getCustomItem($key);

    /**
     * returns all custom items from environment
     *
     * @abstract
     * @return mixed[]
     */
    public function getAllCustomItems();

    /**
     * reset environment
     * save()-call is needed to save changes
     *
     * @abstract
     * @return mixed
     */
    public function clearEnvironment();

    /**
     * sets current assortment tenant which is used for indexing and product lists
     *
     * @param $tenant string
     * @return mixed
     */
    public function setCurrentAssortmentTenant($tenant);

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentTenant();

    /**
     * sets current assortment sub tenant which is used for indexing and product lists
     *
     * @param $subTenant mixed
     * @return mixed
     */
    public function setCurrentAssortmentSubTenant($subTenant);

    /**
     * gets current sub assortment tenant which is used for indexing and product lists
     *
     * @return mixed
     */
    public function getCurrentAssortmentSubTenant();

    /**
     * sets current checkout tenant which is used for cart and checkout manager
     *
     * @param $tenant string
     * @param bool $persistent - if set to false, tenant is not stored to session and only valid for current process
     * @return mixed
     */
    public function setCurrentCheckoutTenant($tenant, $persistent = true);

    /**
     * gets current assortment tenant which is used for cart and checkout manager
     *
     * @return string
     */
    public function getCurrentCheckoutTenant();

    /**
     * returns instance of default currency
     *
     * @return Currency
     */
    public function getDefaultCurrency();

    /**
     * @return boolean
     */
    public function getUseGuestCart();

    /**
     * @param boolean $useGuestCart
     */
    public function setUseGuestCart($useGuestCart);




    /** ===========================================
     *
     *  deprecated functions
     *
     *  ===========================================
     */

    /**
     * @deprecated
     *
     * use setCurrentAssortmentTenant instead
     *
     * @param $tenant string
     * @return mixed
     */
    public function setCurrentTenant($tenant);

    /**
     * @deprecated
     *
     * use getCurrentAssortmentTenant instead
     *
     * @return string
     */
    public function getCurrentTenant();

    /**
     * @deprecated
     *
     * use setCurrentAssortmentSubTenant instead
     *
     * @param $tenant mixed
     * @return mixed
     */
    public function setCurrentSubTenant($tenant);

    /**
     * @deprecated
     *
     * use getCurrentAssortmentSubTenant instead
     *
     * @return mixed
     */
    public function getCurrentSubTenant();


}
