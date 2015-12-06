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


abstract class OnlineShop_Framework_VoucherService_AbstractTokenManager implements OnlineShop_Framework_VoucherService_ITokenManager
{

    /* @var OnlineShop_Framework_AbstractVoucherTokenType */
    public $configuration;

    public $seriesId;

    /* @var OnlineShop_Framework_AbstractVoucherSeries */
    public $series;

    /**
     * @param OnlineShop_Framework_AbstractVoucherTokenType $configuration
     * @throws Exception
     */
    public function __construct(OnlineShop_Framework_AbstractVoucherTokenType $configuration)
    {
        if ($configuration instanceof OnlineShop_Framework_AbstractVoucherTokenType) {
            $this->configuration = $configuration;
            $this->seriesId = $configuration->getObject()->getId();
            $this->series = $configuration->getObject();
        } else {
            throw new Exception("Invalid Configuration Class.");
        }
    }

    /**
     * @return bool
     */
    public abstract function isValidSetting();

    /**
     * @param array $filter
     * @return mixed
     */
    public abstract function cleanUpCodes($filter = []);

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return mixed
     */
    public function checkToken($code, \OnlineShop\Framework\CartManager\ICart $cart){
        $this->checkAllowOncePerCart($code, $cart);
        $this->checkOnlyToken($cart);
    }

    /**
     * Once per cart setting
     *
     * @param $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @throws Exception
     */
    protected function checkAllowOncePerCart($code, \OnlineShop\Framework\CartManager\ICart $cart)
    {
        $cartCodes = $cart->getVoucherTokenCodes();
        if (method_exists($this->configuration, 'getAllowOncePerCart') && $this->configuration->getAllowOncePerCart()) {
            $token = OnlineShop_Framework_VoucherService_Token::getByCode($code);
            if (is_array($cartCodes)) {
                foreach ($cartCodes as $cartCode) {
                    $cartToken = OnlineShop_Framework_VoucherService_Token::getByCode($cartCode);
                    if ($token->getVoucherSeriesId() == $cartToken->getVoucherSeriesId()) {
                        throw new \OnlineShop\Framework\Exception\VoucherServiceException("OncePerCart: Only one token of this series is allowed per cart.", 5);
                    }
                }
            }
        }
    }

    /**
     * Only token per cart setting
     *
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     *
     * @throws Exception
     */
    protected function checkOnlyToken(\OnlineShop\Framework\CartManager\ICart $cart)
    {
        $cartCodes = $cart->getVoucherTokenCodes();
        $cartVoucherCount = sizeof($cartCodes);
        if ($cartVoucherCount && method_exists($this->configuration, 'getOnlyTokenPerCart')) {
            if ($this->configuration->getOnlyTokenPerCart()) {
                throw new \OnlineShop\Framework\Exception\VoucherServiceException("OnlyTokenPerCart: This token is only allowed as only token in this cart.", 6);
            }

            $cartToken = OnlineShop_Framework_VoucherService_Token::getByCode($cartCodes[0]);
            $cartTokenSettings = Object_OnlineShopVoucherSeries::getById($cartToken->getVoucherSeriesId())->getTokenSettings()->getItems()[0];
            if ($cartTokenSettings->getOnlyTokenPerCart()) {
                throw new \OnlineShop\Framework\Exception\VoucherServiceException("OnlyTokenPerCart: There is a token of type onlyToken in your this cart already.", 7);
            }
        }
    }

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool
     */
    public abstract function reserveToken($code, \OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return bool
     */
    public abstract function applyToken($code, \OnlineShop\Framework\CartManager\ICart $cart, OnlineShop_Framework_AbstractOrder $order);

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool
     */
    public abstract function releaseToken($code, \OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * @param null $filter
     * @return array|bool
     */
    public abstract function getCodes($filter = null);

    /**
     * @param null|int $usagePeriod
     * @return bool|array
     */
    public abstract function getStatistics($usagePeriod = null);

    /**
     * @return OnlineShop_Framework_AbstractVoucherTokenType
     */
    public abstract function getConfiguration();

    /**
     * @return bool
     */
    public abstract function insertOrUpdateVoucherSeries();

    /**
     * @return  int
     */
    public abstract function getFinalTokenLength();

    /**
     * @param int $duration
     * @return bool
     */
    public abstract function cleanUpReservations($duration = 0);

    /**
     * @param $view
     * @param array $params
     * @return string The path of the template to display
     */
    public abstract function prepareConfigurationView($view, $params);

}