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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType;
use Pimcore\Model\DataObject\OnlineShopVoucherToken;

/**
 * Interface ITokenManager
 *
 * Manager for specific token settings object of type AbstractVoucherTokenType.
 * Provides functionality for generating, checking, removing, applying, reserving tokens.
 * Also prepares the view of the Voucher Code Tab and specifies its template.
 */
interface ITokenManager
{
    /**
     * @param AbstractVoucherTokenType $configuration
     */
    public function __construct(AbstractVoucherTokenType $configuration);

    /**
     * Checks if the configuration objects contains valid values to
     * generate the new token(s).
     *
     * @return bool
     */
    public function isValidSetting();

    /**
     * Removes tokens of series, if no parameters are passed, all tokens get removed from series.
     *
     * @param array|null $filter Associative with the indices: "usage" and "olderThan".
     *
     * @return mixed
     */
    public function cleanUpCodes($filter = []);

    /**
     * Checks a token code, if it is available for putting into cart
     * e.g. it is not reserved or used, or other tokenType specific settings.
     *
     * @param string $code
     * @param ICart $cart
     *
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException
     *
     * @return bool
     */
    public function checkToken($code, ICart $cart);

    /**
     * Adds a reservation to a specific token code.
     *
     * @param string $code
     * @param ICart $cart
     *
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException
     *
     * @return bool
     */
    public function reserveToken($code, ICart $cart);

    /**
     * Creates token object and adds it to order, increases token usage and
     * clears the reservation of the token.
     *
     * @param string $code
     * @param ICart $cart
     * @param AbstractOrder $order
     *
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException
     *
     * @return bool|\Pimcore\Model\DataObject\OnlineShopVoucherToken
     */
    public function applyToken($code, ICart $cart, AbstractOrder $order);

    /**
     * Removes the reservation of a token code.
     *
     * @param string $code
     * @param ICart $cart
     *
     * @return bool
     */
    public function releaseToken($code, ICart $cart);

    /**
     * cleans up the token usage and the ordered token object if necessary
     *
     * @param \Pimcore\Model\DataObject\OnlineShopVoucherToken $tokenObject
     * @param AbstractOrder $order
     *
     * @return bool
     */
    public function removeAppliedTokenFromOrder(OnlineShopVoucherToken $tokenObject, AbstractOrder $order);

    /**
     * Get the codes of a voucher series, optionally a filter array can be passed.
     *
     * @param array|null $filter
     *
     * @return array|bool
     */
    public function getCodes($filter = null);

    /**
     * @param null|int $usagePeriod
     *
     * @return bool|array
     */
    public function getStatistics($usagePeriod = null);

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
     *
     * @return bool
     */
    public function cleanUpReservations($duration = 0);

    /**
     * Prepares the view and returns the according template for rendering.
     * Gets the codes according to paging and filter params and sets
     * error/success messages, settings and statistics for view.
     *
     * @param $viewParamsBag
     * @param array $params All params, especially for filtering and ordering token codes.
     *
     * @return string The path of the template to display.
     */
    public function prepareConfigurationView(&$viewParamsBag, $params);

    /**
     * @return AbstractVoucherTokenType
     */
    public function getConfiguration();
}
