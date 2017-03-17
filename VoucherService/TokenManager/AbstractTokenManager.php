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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\TokenManager;

use OnlineShop\Framework\Exception\VoucherServiceException;
use OnlineShop\Framework\VoucherService\Token;
use Pimcore\Model\Object\OnlineShopVoucherSeries;
use Pimcore\View\Helper\TranslateAdmin;

abstract class AbstractTokenManager implements ITokenManager
{

    /* @var \OnlineShop\Framework\Model\AbstractVoucherTokenType */
    public $configuration;

    public $seriesId;

    /* @var \OnlineShop\Framework\Model\AbstractVoucherSeries */
    public $series;

    /**
     * @param \OnlineShop\Framework\Model\AbstractVoucherTokenType $configuration
     * @throws VoucherServiceException
     */
    public function __construct(\OnlineShop\Framework\Model\AbstractVoucherTokenType $configuration)
    {
        if ($configuration instanceof \OnlineShop\Framework\Model\AbstractVoucherTokenType) {
            $this->configuration = $configuration;
            $this->seriesId = $configuration->getObject()->getId();
            $this->series = $configuration->getObject();
        } else {
            throw new VoucherServiceException("Invalid Configuration Class.");
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
     * @throws VoucherServiceException
     */
    protected function checkAllowOncePerCart($code, \OnlineShop\Framework\CartManager\ICart $cart)
    {
        $cartCodes = $cart->getVoucherTokenCodes();
        if (method_exists($this->configuration, 'getAllowOncePerCart') && $this->configuration->getAllowOncePerCart()) {
            $token = \OnlineShop\Framework\VoucherService\Token::getByCode($code);
            if (is_array($cartCodes)) {
                foreach ($cartCodes as $cartCode) {
                    $cartToken = \OnlineShop\Framework\VoucherService\Token::getByCode($cartCode);
                    if ($token->getVoucherSeriesId() == $cartToken->getVoucherSeriesId()) {
                        throw new VoucherServiceException("OncePerCart: Only one token of this series is allowed per cart.", 5);
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
     * @throws VoucherServiceException
     */
    protected function checkOnlyToken(\OnlineShop\Framework\CartManager\ICart $cart)
    {
        $cartCodes = $cart->getVoucherTokenCodes();
        $cartVoucherCount = sizeof($cartCodes);
        if ($cartVoucherCount && method_exists($this->configuration, 'getOnlyTokenPerCart')) {
            if ($this->configuration->getOnlyTokenPerCart()) {
                throw new VoucherServiceException("OnlyTokenPerCart: This token is only allowed as only token in this cart.", 6);
            }

            $cartToken = \OnlineShop\Framework\VoucherService\Token::getByCode($cartCodes[0]);
            $cartTokenSettings = OnlineShopVoucherSeries::getById($cartToken->getVoucherSeriesId())->getTokenSettings()->getItems()[0];
            if ($cartTokenSettings->getOnlyTokenPerCart()) {
                throw new VoucherServiceException("OnlyTokenPerCart: There is a token of type onlyToken in your this cart already.", 7);
            }
        }
    }

    /**
     * Export tokens to CSV
     *
     * @param $params
     * @return mixed
     * @implements IExportableTokenManager
     */
    public function exportCsv(array $params)
    {
        $translator = new TranslateAdmin();

        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, [
            $translator->translateAdmin('plugin_onlineshop_voucherservice_table-token'),
            $translator->translateAdmin('plugin_onlineshop_voucherservice_table-usages'),
            $translator->translateAdmin('plugin_onlineshop_voucherservice_table-length'),
            $translator->translateAdmin('plugin_onlineshop_voucherservice_table-date'),
        ]);

        $data = null;

        try {
            $data = $this->getExportData($params);
        } catch (\Exception $e) {
            fputcsv($stream, [$e->getMessage()]);
            fputcsv($stream, '');
        }

        if (null !== $data && is_array($data)) {
            foreach ($data as $tokenInfo) {
                fputcsv($stream, [
                    $tokenInfo['token'],
                    (int) $tokenInfo['usages'],
                    (int) $tokenInfo['length'],
                    $tokenInfo['timestamp']
                ]);
            }
        }

        rewind($stream);
        $result = stream_get_contents($stream);
        fclose($stream);

        return $result;
    }

    /**
     * Export tokens to plain text list
     *
     * @param $params
     * @return mixed
     * @implements IExportableTokenManager
     */
    public function exportPlain(array $params)
    {
        $result = [];
        $data   = null;

        try {
            $data = $this->getExportData($params);
        } catch (\Exception $e) {
            $result[] = $e->getMessage();
            $result[] = '';
        }

        if (null !== $data && is_array($data)) {
            /** @var Token $token */
            foreach ($data as $tokenInfo) {
                $result[] = $tokenInfo['token'];
            }
        }

        return implode(PHP_EOL, $result) . PHP_EOL;
    }

    /**
     * Get data for export - to be overridden in child classes
     *
     * @param array $params
     * @return array
     * @throws \Exception
     * @throws \Zend_Paginator_Exception
     */
    protected function getExportData(array $params)
    {
        return [];
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
     * @param \OnlineShop\Framework\Model\AbstractOrder $order
     * @return bool
     */
    public abstract function applyToken($code, \OnlineShop\Framework\CartManager\ICart $cart, \OnlineShop\Framework\Model\AbstractOrder $order);

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
     * @return \OnlineShop\Framework\Model\AbstractVoucherTokenType
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
     * @param $viewParamsBag
     * @param array $params
     * @return string The path of the template to display
     */
    public abstract function prepareConfigurationView(&$viewParamsBag, $params);

}
