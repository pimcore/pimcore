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

use Knp\Component\Pager\PaginatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Statistic;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypeSingle;
use Pimcore\Model\DataObject\OnlineShopVoucherToken;

/**
 * @property \Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypeSingle $configuration
 */
class Single extends AbstractTokenManager implements ExportableTokenManagerInterface
{
    protected string $template;

    public function __construct(AbstractVoucherTokenType $configuration, protected PaginatorInterface $paginator)
    {
        parent::__construct($configuration);
        if ($configuration instanceof VoucherTokenTypeSingle) {
            $this->template = '@PimcoreEcommerceFramework/voucher/voucher_code_tab_single.html.twig';
        } else {
            throw new InvalidConfigException('Invalid Configuration Class for type VoucherTokenTypeSingle.');
        }
    }

    public function isValidSetting(): bool
    {
        // TODO do some character matching etc
        return true;
    }

    public function cleanUpCodes(?array $filter = []): bool
    {
        return true;
    }

    public function cleanupReservations(int $duration = 0, ?int $seriesId = null): bool
    {
        return Reservation::cleanUpReservations($duration, $seriesId);
    }

    public function prepareConfigurationView(array &$viewParamsBag, array $params): string
    {
        $codes = $this->getCodes();
        if ($codes && $this->getConfiguration()->getToken() != $codes[0]['token']) {
            $viewParamsBag['generateWarning'] = 'bundle_ecommerce_voucherservice_msg-error-overwrite-single';
            $viewParamsBag['settings']['Original Token'] = $codes[0];
        }

        if ($codes) {
            $page = (int)($params['page'] ?? 1);
            $perPage = (int)($params['tokensPerPage'] ?? 25);

            $total = count($codes);

            $availablePages = (int) ceil($total / $perPage);
            $page = min($page, $availablePages);

            $paginator = $this->paginator->paginate(
                (array)$codes,
                $page ?: 1,
                $perPage
            );
            $viewParamsBag['paginator'] = $paginator;
            $viewParamsBag['count'] = $total;
        }

        $viewParamsBag['msg']['error'] = $params['error'] ?? null;
        $viewParamsBag['msg']['success'] = $params['success'] ?? null;

        $viewParamsBag['settings'] = [
            'bundle_ecommerce_voucherservice_settings-token' => $this->getConfiguration()->getToken(),
            'bundle_ecommerce_voucherservice_settings-max-usages' => $this->getConfiguration()->getUsages(),
        ];

        $statisticUsagePeriod = 30;
        if (isset($params['statisticUsagePeriod'])) {
            $statisticUsagePeriod = $params['statisticUsagePeriod'];
        }
        $viewParamsBag['statistics'] = $this->getStatistics($statisticUsagePeriod);

        return $this->template;
    }

    /**
     * Get data for export
     *
     * @param array $params
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getExportData(array $params): array
    {
        $data = [];
        if ($codes = $this->getCodes()) {
            foreach ($codes as $code) {
                $data[] = $code;
            }
        }

        return $data;
    }

    public function getFinalTokenLength(): int
    {
        return strlen($this->configuration->getToken());
    }

    /**
     * @return bool | array | string - bool if failed - string if successfully created
     */
    public function insertOrUpdateVoucherSeries(): bool|string|array
    {
        $db = \Pimcore\Db::get();

        try {
            $query =
                'INSERT INTO ' . Token\Dao::TABLE_NAME . '(token,length,voucherSeriesId) VALUES (?,?,?)
                    ON DUPLICATE KEY UPDATE token = ?, length = ?';

            $db->executeQuery($query, [trim($this->configuration->getToken()), $this->getFinalTokenLength(), $this->getSeriesId(), trim($this->configuration->getToken()), $this->getFinalTokenLength()]);

            return trim($this->configuration->getToken());
        } catch (\Exception $e) {
            Logger::error((string) $e);
        }

        return false;
    }

    /**
     * @param array|null $filter
     *
     * @return array|bool
     */
    public function getCodes(array $filter = null): bool|array
    {
        return Token\Listing::getCodes($this->seriesId, $filter);
    }

    protected function prepareUsageStatisticData(array &$data, ?int $usagePeriod): void
    {
        $now = new \DateTime();
        $periodData = [];
        for ($i = $usagePeriod; $i > 0; $i--) {
            $index = $now->format('Y-m-d');
            $periodData[$index] = isset($data[$index]) ? $data[$index] : 0;
            $now->modify('-1 day');
        }
        $data = $periodData;
    }

    public function getStatistics(int $usagePeriod = null): array
    {
        $token = Token::getByCode($this->configuration->getToken());
        $overallCount = $this->configuration->getUsages();
        $usageCount = $token ? $token->getUsages() : 0;
        $reservedTokenCount = (int) Token\Listing::getCountByReservation($this->seriesId);

        $usage = Statistic::getBySeriesId($this->seriesId, $usagePeriod);
        $this->prepareUsageStatisticData($usage, $usagePeriod);

        return [
            'overallCount' => $overallCount,
            'usageCount' => $usageCount,
            'freeCount' => $overallCount - $usageCount - $reservedTokenCount,
            'reservedCount' => $reservedTokenCount,
            'usage' => $usage,
        ];
    }

    public function reserveToken(string $code, CartInterface $cart): bool
    {
        if (Token::getByCode($code)) {
            if (Reservation::create($code, $cart)) {
                return true;
            }
        }

        return false;
    }

    public function applyToken(string $code, CartInterface $cart, AbstractOrder $order): OnlineShopVoucherToken|bool
    {
        if ($token = Token::getByCode($code)) {
            if ($token->check($this->configuration->getUsages(), true)) {
                if ($token->apply()) {
                    $orderToken = \Pimcore\Model\DataObject\OnlineShopVoucherToken::getByToken($code, 1);
                    if (!$orderToken instanceof \Pimcore\Model\DataObject\OnlineShopVoucherToken) {
                        $orderToken = new \Pimcore\Model\DataObject\OnlineShopVoucherToken();
                        $orderToken->setTokenId($token->getId());
                        $orderToken->setToken($token->getToken());
                        $series = \Pimcore\Model\DataObject\OnlineShopVoucherSeries::getById($token->getVoucherSeriesId());
                        $orderToken->setVoucherSeries($series);
                        $orderToken->setParent($series);        // TODO set correct parent for applied tokens
                        $orderToken->setKey(\Pimcore\File::getValidFilename($token->getToken()));
                        $orderToken->setPublished(true);
                        $orderToken->save();
                    }

                    return $orderToken;
                }
            }
        }

        return false;
    }

    /**
     * cleans up the token usage and the ordered token object if necessary
     *
     * @param OnlineShopVoucherToken $tokenObject
     * @param AbstractOrder $order
     *
     * @return bool
     */
    public function removeAppliedTokenFromOrder(OnlineShopVoucherToken $tokenObject, AbstractOrder $order): bool
    {
        if ($token = Token::getByCode($tokenObject->getToken())) {
            return $token->unuse();
        }

        return false;
    }

    public function releaseToken(string $code, CartInterface $cart): bool
    {
        return Reservation::releaseToken($code, $cart);
    }

    public function checkToken(string $code, CartInterface $cart): bool
    {
        parent::checkToken($code, $cart);
        if ($token = Token::getByCode($code)) {
            if ($token->check((int)$this->configuration->getUsages())) {
                return true;
            }
        }

        return false;
    }

    public function getConfiguration(): VoucherTokenTypeSingle
    {
        return $this->configuration;
    }

    public function setConfiguration(VoucherTokenTypeSingle $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getSeriesId(): int|null
    {
        return $this->seriesId;
    }

    public function setSeriesId(int|null $seriesId): void
    {
        $this->seriesId = $seriesId;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
