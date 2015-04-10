<?php

/**
 * Interface OnlineShop_Framework_ICachingPriceSystem
 */
interface OnlineShop_Framework_ICachingPriceSystem extends OnlineShop_Framework_IPriceSystem {

    /**
     * load price infos once for gives product entries and caches them
     *
     * @param $productEntries
     * @param $options
     * @return mixed
     */
    public function loadPriceInfos($productEntries, $options);

    /**
     * clears cached price infos
     *
     * @param $productEntries
     * @param $options
     * @return mixed
     */
    public function clearPriceInfos($productEntries,$options);

}
