<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework;

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
     * @param $subTenant string
     * @return mixed
     */
    public function setCurrentAssortmentSubTenant($subTenant);

    /**
     * gets current sub assortment tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentSubTenant();

    /**
     * sets current checkout tenant which is used for cart and checkout manager
     *
     * @param $tenant string
     * @return mixed
     */
    public function setCurrentCheckoutTenant($tenant);

    /**
     * gets current assortment tenant which is used for cart and checkout manager
     *
     * @return string
     */
    public function getCurrentCheckoutTenant();

    /**
     * @return \Zend_Locale
     */
    public function getCurrencyLocale();

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
     * @param $tenant string
     * @return mixed
     */
    public function setCurrentSubTenant($tenant);

    /**
     * @deprecated
     *
     * use getCurrentAssortmentSubTenant instead
     *
     * @return string
     */
    public function getCurrentSubTenant();


}
