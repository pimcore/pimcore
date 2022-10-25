<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherSeries;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token;
use Pimcore\Model\DataObject\OnlineShopVoucherSeries;
use Pimcore\Model\DataObject\OnlineShopVoucherToken;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTokenManager implements TokenManagerInterface, ExportableTokenManagerInterface
{
    public AbstractVoucherTokenType $configuration;

    public string|int|null $seriesId;

    public AbstractVoucherSeries $series;

    public function __construct(AbstractVoucherTokenType $configuration)
    {
        $this->configuration = $configuration;
        /** @var AbstractVoucherSeries $series */
        $series = $configuration->getObject();
        $this->seriesId = $series->getId();
        $this->series = $series;
    }

    abstract public function isValidSetting(): bool;

    abstract public function cleanUpCodes(?array $filter = []): bool;

    /**
     * @param string $code
     * @param CartInterface $cart
     *
     * @return bool
     *
     * @throws VoucherServiceException When validation fails for any reason
     */
    public function checkToken(string $code, CartInterface $cart): bool
    {
        $this->checkVoucherSeriesIsPublished($code);
        $this->checkAllowOncePerCart($code, $cart);
        $this->checkOnlyToken($cart);

        return true;
    }

    /**
     * Only tokens of published voucher series' may be used.
     *
     * @param string $code
     *
     * @throws VoucherServiceException When token for $code can't be found, series of token can't be found or if series isn't published.
     */
    protected function checkVoucherSeriesIsPublished(string $code)
    {
        $token = Token::getByCode($code);
        if (!$token) {
            throw new VoucherServiceException("No token found for code '" . $code . "'", VoucherServiceException::ERROR_CODE_NO_TOKEN_FOR_THIS_CODE_EXISTS);
        }
        /** @var OnlineShopVoucherSeries|null $series */
        $series = OnlineShopVoucherSeries::getById($token->getVoucherSeriesId());
        if (!$series) {
            throw new VoucherServiceException("No voucher series found for token '" . $token->getToken() . "' (ID " . $token->getId() . ')', VoucherServiceException::ERROR_CODE_NO_TOKEN_FOR_THIS_CODE_EXISTS);
        }
        if (!$series->isPublished()) {
            throw new VoucherServiceException("Voucher series '" . $series->getName() . "' (ID " . $series->getId() . ") of token '" . $token->getToken() . "' (ID " . $token->getId() . ") isn't published", VoucherServiceException::ERROR_CODE_NO_TOKEN_FOR_THIS_CODE_EXISTS);
        }
    }

    /**
     * Once per cart setting
     *
     * @param string $code
     * @param CartInterface $cart
     *
     * @throws VoucherServiceException
     */
    protected function checkAllowOncePerCart(string $code, CartInterface $cart)
    {
        $cartCodes = $cart->getVoucherTokenCodes();
        if (method_exists($this->configuration, 'getAllowOncePerCart') && $this->configuration->getAllowOncePerCart()) {
            $token = Token::getByCode($code);
            if (is_array($cartCodes)) {
                foreach ($cartCodes as $cartCode) {
                    $cartToken = Token::getByCode($cartCode);
                    if ($token->getVoucherSeriesId() == $cartToken->getVoucherSeriesId()) {
                        throw new VoucherServiceException('OncePerCart: Only one token of this series is allowed per cart.', VoucherServiceException::ERROR_CODE_ONCE_PER_CART_VIOLATED);
                    }
                }
            }
        }
    }

    /**
     * Only token per cart setting
     *
     * @param CartInterface $cart
     *
     * @throws VoucherServiceException
     */
    protected function checkOnlyToken(CartInterface $cart)
    {
        $cartCodes = $cart->getVoucherTokenCodes();
        $cartVoucherCount = count($cartCodes);
        if ($cartVoucherCount && method_exists($this->configuration, 'getOnlyTokenPerCart')) {
            if ($this->configuration->getOnlyTokenPerCart()) {
                throw new VoucherServiceException('OnlyTokenPerCart: This token is only allowed as only token in this cart.', VoucherServiceException::ERROR_CODE_ONLY_TOKEN_PER_CART_CANNOT_BE_ADDED);
            }

            $cartToken = Token::getByCode($cartCodes[0]);
            /** @var AbstractVoucherTokenType $cartTokenSettings */
            $cartTokenSettings = OnlineShopVoucherSeries::getById($cartToken->getVoucherSeriesId())->getTokenSettings()->getItems()[0];
            if ($cartTokenSettings->getOnlyTokenPerCart()) {
                throw new VoucherServiceException('OnlyTokenPerCart: There is a token of type onlyToken in your this cart already.', VoucherServiceException::ERROR_CODE_ONLY_TOKEN_PER_CART_ALREADY_ADDED);
            }
        }
    }

    /**
     * Export tokens to CSV
     *
     * @param array $params
     *
     * @return string
     */
    public function exportCsv(array $params): string
    {
        $translator = \Pimcore::getContainer()->get(TranslatorInterface::class);

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
            fputcsv($stream, ['']);
        }

        if (null !== $data && is_array($data)) {
            foreach ($data as $tokenInfo) {
                fputcsv($stream, [
                    $tokenInfo['token'],
                    (int) $tokenInfo['usages'],
                    (int) $tokenInfo['length'],
                    $tokenInfo['timestamp'],
                ]);
            }
        }

        rewind($stream);
        $result = stream_get_contents($stream);
        fclose($stream);
        if(is_bool($result)) {
            return '';
        }
        return $result;
    }

    /**
     * Export tokens to plain text list
     *
     * @param array $params
     *
     * @return string
     */
    public function exportPlain(array $params): string
    {
        $result = [];
        $data = null;

        try {
            $data = $this->getExportData($params);
        } catch (\Exception $e) {
            $result[] = $e->getMessage();
            $result[] = '';
        }

        if (null !== $data && is_array($data)) {
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
    protected function getExportData(array $params): array
    {
        return [];
    }

    abstract public function reserveToken(string $code, CartInterface $cart): bool;

    abstract public function applyToken(string $code, CartInterface $cart, \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order): OnlineShopVoucherToken|bool;

    abstract public function releaseToken(string $code, CartInterface $cart): bool;

    /**
     * @param array|null $filter
     *
     * @return array|bool
     */
    abstract public function getCodes(array $filter = null): bool|array;

    /**
     * @param int|null $usagePeriod
     *
     * @return bool|array
     */
    abstract public function getStatistics(int $usagePeriod = null): bool|array;

    abstract public function getConfiguration(): AbstractVoucherTokenType;

    /**
     * Returns bool false if failed - otherwise an array or a string with the codes
     *
     * @return bool | string | array
     */
    abstract public function insertOrUpdateVoucherSeries(): bool|string|array;

    abstract public function getFinalTokenLength(): int;

    /**
     * {@inheritdoc}
     */
    abstract public function cleanUpReservations(int $duration = 0): bool;

    /**
     * @param array $viewParamsBag
     * @param array $params
     *
     * @return string The path of the template to display
     */
    abstract public function prepareConfigurationView(array &$viewParamsBag, array $params): string;
}
