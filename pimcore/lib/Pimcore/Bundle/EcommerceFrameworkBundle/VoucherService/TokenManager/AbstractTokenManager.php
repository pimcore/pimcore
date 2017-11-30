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
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherSeries;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token;
use Pimcore\Model\DataObject\OnlineShopVoucherSeries;

abstract class AbstractTokenManager implements ITokenManager
{
    /* @var AbstractVoucherTokenType */
    public $configuration;

    public $seriesId;

    /* @var AbstractVoucherSeries */
    public $series;

    /**
     * @param AbstractVoucherTokenType $configuration
     *
     * @throws VoucherServiceException
     */
    public function __construct(AbstractVoucherTokenType $configuration)
    {
        if ($configuration instanceof AbstractVoucherTokenType) {
            $this->configuration = $configuration;
            $this->seriesId = $configuration->getObject()->getId();
            $this->series = $configuration->getObject();
        } else {
            throw new VoucherServiceException('Invalid Configuration Class.');
        }
    }

    /**
     * @return bool
     */
    abstract public function isValidSetting();

    /**
     * @param array $filter
     *
     * @return mixed
     */
    abstract public function cleanUpCodes($filter = []);

    /**
     * @param string $code
     * @param ICart $cart
     *
     * @return mixed
     */
    public function checkToken($code, ICart $cart)
    {
        $this->checkAllowOncePerCart($code, $cart);
        $this->checkOnlyToken($cart);
    }

    /**
     * Once per cart setting
     *
     * @param $code
     * @param ICart $cart
     *
     * @throws VoucherServiceException
     */
    protected function checkAllowOncePerCart($code, ICart $cart)
    {
        $cartCodes = $cart->getVoucherTokenCodes();
        if (method_exists($this->configuration, 'getAllowOncePerCart') && $this->configuration->getAllowOncePerCart()) {
            $token = Token::getByCode($code);
            if (is_array($cartCodes)) {
                foreach ($cartCodes as $cartCode) {
                    $cartToken = Token::getByCode($cartCode);
                    if ($token->getVoucherSeriesId() == $cartToken->getVoucherSeriesId()) {
                        throw new VoucherServiceException('OncePerCart: Only one token of this series is allowed per cart.', 5);
                    }
                }
            }
        }
    }

    /**
     * Only token per cart setting
     *
     * @param ICart $cart
     *
     * @throws VoucherServiceException
     */
    protected function checkOnlyToken(ICart $cart)
    {
        $cartCodes = $cart->getVoucherTokenCodes();
        $cartVoucherCount = sizeof($cartCodes);
        if ($cartVoucherCount && method_exists($this->configuration, 'getOnlyTokenPerCart')) {
            if ($this->configuration->getOnlyTokenPerCart()) {
                throw new VoucherServiceException('OnlyTokenPerCart: This token is only allowed as only token in this cart.', 6);
            }

            $cartToken = Token::getByCode($cartCodes[0]);
            $cartTokenSettings = OnlineShopVoucherSeries::getById($cartToken->getVoucherSeriesId())->getTokenSettings()->getItems()[0];
            if ($cartTokenSettings->getOnlyTokenPerCart()) {
                throw new VoucherServiceException('OnlyTokenPerCart: There is a token of type onlyToken in your this cart already.', 7);
            }
        }
    }

    /**
     * Export tokens to CSV
     *
     * @param $params
     *
     * @return mixed
     * @implements IExportableTokenManager
     */
    public function exportCsv(array $params)
    {
       $translator = \Pimcore::getContainer()->get("pimcore.translator");

        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, [
            $translator->trans('bundle_ecommerce_voucherservice_table-token', [], 'admin'),
            $translator->trans('bundle_ecommerce_voucherservice_table-usages', [], 'admin'),
            $translator->trans('bundle_ecommerce_voucherservice_table-length', [], 'admin'),
            $translator->trans('bundle_ecommerce_voucherservice_table-date', [], 'admin'),
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
     *
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
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getExportData(array $params)
    {
        return [];
    }

    /**
     * @param string $code
     * @param ICart $cart
     *
     * @return bool
     */
    abstract public function reserveToken($code, ICart $cart);

    /**
     * @param string $code
     * @param ICart $cart
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order
     *
     * @return bool
     */
    abstract public function applyToken($code, ICart $cart, \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order);

    /**
     * @param string $code
     * @param ICart $cart
     *
     * @return bool
     */
    abstract public function releaseToken($code, ICart $cart);

    /**
     * @param null $filter
     *
     * @return array|bool
     */
    abstract public function getCodes($filter = null);

    /**
     * @param null|int $usagePeriod
     *
     * @return bool|array
     */
    abstract public function getStatistics($usagePeriod = null);

    /**
     * @return AbstractVoucherTokenType
     */
    abstract public function getConfiguration();

    /**
     * @return bool
     */
    abstract public function insertOrUpdateVoucherSeries();

    /**
     * @return  int
     */
    abstract public function getFinalTokenLength();

    /**
     * @param int $duration
     *
     * @return bool
     */
    abstract public function cleanUpReservations($duration = 0);

    /**
     * @param $viewParamsBag
     * @param array $params
     *
     * @return string The path of the template to display
     */
    abstract public function prepareConfigurationView(&$viewParamsBag, $params);
}
