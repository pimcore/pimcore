<?php

interface OnlineShop_Framework_IEnvironment extends OnlineShop_Framework_IComponent {
    /**
     * @abstract
     * @return int
     */
    public function getCurrentUserId();

    /**
     * @param int $userId
     * @return void
     */
    public function setCurrentUserId($userId);

    public function setCustomItem($key, $value);
    public function removeCustomItem($key);

    public function getCustomItem($key);
    public function getAllCustomItems();
}
