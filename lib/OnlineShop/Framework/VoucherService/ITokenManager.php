<?php

/**
 * Interface OnlineShop_Framework_VoucherService_ITokenManager
 */
interface OnlineShop_Framework_VoucherService_ITokenManager
{

    public function __construct(OnlineShop_Framework_AbstractVoucherTokenType $configuration);

    /**
     * Checks whether the configuration obejcts contains valid values to
     * generate the new token(s).
     *
     * @return bool
     */
    public function isValidSetting();

    /**
     * Removes tokens von series, if no parameters are passed, the all tokens get removed from series.
     *
     * @param array|null $filter Associative with the indices: "used", "unused" and "olderThan".
     *
     * @return mixed
     */
    public function cleanUpCodes($filter = []);

    /**
     * Checks a token code, whether it is available for putting into cart
     * e.g. it is not reserved or used.
     *
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function checkToken($code, OnlineShop_Framework_ICart $cart);

    /**
     * Adds a reservation to a specific token code.
     *
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function reserveToken($code, OnlineShop_Framework_ICart $cart);

    /**
     * Creates token object and adds it to order, increases token usage and
     * clears the reservation of the token.
     *
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return bool|\Pimcore\Model\Object\OnlineShopVoucherToken
     */
    public function applyToken($code, OnlineShop_Framework_ICart $cart, OnlineShop_Framework_AbstractOrder $order);

    /**
     * Removes the reservation of a token code.
     *
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function releaseToken($code, OnlineShop_Framework_ICart $cart);

    /**
     * Get the codes of a voucher series, optionally a filter array can be passed.
     *
     * @param array|null $filter
     * @return array|bool
     */
    public function getCodes($filter = null);

    /**
     * @param null|int $usagePeriod
     * @return bool|array
     */
    public function getStatistics($usagePeriod = null);

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

    /**
     * @param int $duration
     * @return bool
     */
    public function cleanUpReservations($duration = 0);

    /**
     * @param $view
     * @param array $params
     * @return string The path of the template to display
     */
    public function prepareConfigurationView($view, $params);

}