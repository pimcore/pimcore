<?php

/**
 * Interface OnlineShop_Framework_VoucherService_ITokenManager
 */
interface OnlineShop_Framework_VoucherService_ITokenManager
{

    public function __construct(OnlineShop_Framework_AbstractVoucherTokenType $configuration);

    /**
     * @return bool
     */
    public function isValidSetting();

    /**
     * @return bool
     */
    public function cleanUpCodes();

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function checkToken($code, OnlineShop_Framework_ICart $cart);

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function reserveToken($code, OnlineShop_Framework_ICart $cart);

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return bool|Object_OnlineShopVoucherToken
     */
    public function applyToken($code, OnlineShop_Framework_ICart $cart, OnlineShop_Framework_AbstractOrder $order);

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function releaseToken($code, OnlineShop_Framework_ICart $cart);

    /**
     * @param array|null $params
     * @return array|bool
     */
    public function getCodes($params = null);

    /**
     * @return array
     */
    public function getStatistics();

    /**
     * @return OnlineShop_Framework_AbstractVoucherTokenType
     */
    public function getConfiguration();

    /**
     * @return bool
     */
    public function insertOrUpdateVoucherSeries();

    /**
     * @return  int
     */
    public function getFinalTokenLength();

    public function cleanUpReservations($duration = 0);

    /**
     * @param $view
     * @param array $params
     * @return string
     */
    public function prepareConfigurationView($view, $params);

}