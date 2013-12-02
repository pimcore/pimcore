<?php

/**
 * Interface for environment implementations of online shop framework
 */
interface OnlineShop_Framework_IEnvironment extends OnlineShop_Framework_IComponent {

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
     * @param $tenant string
     * @return mixed
     */
    public function setCurrentTenant($tenant);

    /**
     * @return string
     */
    public function getCurrentTenant();

    /**
     * @param $tenant string
     * @return mixed
     */
    public function setCurrentSubTenant($tenant);

    /**
     * @return string
     */
    public function getCurrentSubTenant();

    /**
     * @return Zend_Locale
     */
    public function getCurrencyLocale();
}
