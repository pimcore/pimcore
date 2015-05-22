<?php

interface OnlineShop_Framework_IVoucherService
{
    /**
     * @param string $code
     * @param Onlineshop_Framework_ICart $cart
     * @return bool
     */
    public function checkToken($code, Onlineshop_Framework_ICart $cart);

    /**
     * @param string $code
     * @param Onlineshop_Framework_ICart $cart
     * @return bool
     */
    public function reserveToken($code, Onlineshop_Framework_ICart $cart);

    /**
     * @param string $code
     * @param Onlineshop_Framework_ICart $cart
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return bool
     */
    public function applyToken($code, Onlineshop_Framework_ICart $cart, OnlineShop_Framework_AbstractOrder $order);

    /**
     * @param string $code
     * @param Onlineshop_Framework_ICart $cart
     * @return bool
     */
    public function releaseToken($code, Onlineshop_Framework_ICart $cart);

    /**
     * @param null|int $duration
     * @param null|string $seriesId
     * @return bool
     */
    public function cleanUpReservations($duration = null, $seriesId = null);

    /**
     * @param \Pimcore\Model\Object\OnlineShopVoucherSeries $series
     * @return bool
     */
    public function cleanUpVoucherSeries(\Pimcore\Model\Object\OnlineShopVoucherSeries $series);

    /**
     * @param null|int $duration days
     * @param null|string $seriesId
     * @return bool
     */
    public function cleanUpStatistics($duration = null, $seriesId = null);
}