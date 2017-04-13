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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService;

interface IVoucherService
{
    /**
     * Voucherservice get initialized with config settings from Onlineshop Config.
     *
     * @param $sysConfig
     */
    public function __construct($sysConfig);

    /**
     * Gets the correct token manager and calls its checkToken() function.
     *
     * @param string $code
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     *
     * @return bool
     *
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException
     */
    public function checkToken($code, \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * Gets the correct token manager and calls its reserveToken() function.
     *
     * @param string $code
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     *
     * @return bool
     */
    public function reserveToken($code, \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * Gets the correct token manager and calls its releaseToken() function, which removes a reservations.
     *
     * @param string $code
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     *
     * @return bool
     */
    public function releaseToken($code, \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * Gets the correct token manager and calls its applyToken() function, which returns
     * the ordered token object which gets appended to the order object. The token
     * reservations gets released.
     *
     * @param string $code
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order
     *
     * @return bool
     */
    public function applyToken($code, \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart, \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order);

    /**
     * Gets the correct token manager and calls removeAppliedTokenFromOrder(), which cleans up the
     * token usage and the ordered token object if necessary, removes the token object from the order.
     *
     * @param \Pimcore\Model\Object\OnlineShopVoucherToken $tokenObject
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order
     *
     * @return mixed
     */
    public function removeAppliedTokenFromOrder(\Pimcore\Model\Object\OnlineShopVoucherToken $tokenObject, \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order);

    /**
     * Cleans the token reservations due to sysConfig duration settings, if no series Id is
     * set all reservations older than the set duration get removed.
     *
     * @param null|string $seriesId
     *
     * @return bool
     */
    public function cleanUpReservations($seriesId = null);

    /**
     * Removes all tokens from a voucher series and its reservations,
     * not considering any type of filter.
     *
     * @param \Pimcore\Model\Object\OnlineShopVoucherSeries $series
     *
     * @return bool
     */
    public function cleanUpVoucherSeries(\Pimcore\Model\Object\OnlineShopVoucherSeries $series);

    /**
     * Removes all statistics, optionally a seriesId can be passed, to only remove from one series.
     *
     * @param null|string $seriesId
     *
     * @return bool
     */
    public function cleanUpStatistics($seriesId = null);
}
