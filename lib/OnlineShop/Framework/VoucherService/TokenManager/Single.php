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


class OnlineShop_Framework_VoucherService_TokenManager_Single extends OnlineShop_Framework_VoucherService_AbstractTokenManager
{

    protected $template;

    public function __construct(\OnlineShop\Framework\Model\AbstractVoucherTokenType $configuration)
    {
        parent::__construct($configuration);
        if ($configuration instanceof \Pimcore\Model\Object\Fieldcollection\Data\VoucherTokenTypeSingle) {
            $this->template =  "voucher/voucher-code-tab-single.php";
        } else {
            throw new Exception("Invalid Configuration Class for type VoucherTokenTypeSingle.");
        }
    }

    /**
     * @return bool
     */
    public function isValidSetting()
    {
        // TODO do some character matching etc
        return true;
    }

    /**
     * @return bool
     */
    public function cleanUpCodes($filter = [])
    {
    }


    public function cleanupReservations($duration = 0, $seriesId = null)
    {
        return OnlineShop_Framework_VoucherService_Reservation::cleanUpReservations($duration, $seriesId);
    }

    /**
     * @param $view
     * @param array $params
     * @return string
     * @throws Zend_Paginator_Exception
     */
    public function prepareConfigurationView($view, $params)
    {
        if ($this->getConfiguration()->getToken() != $this->getCodes()[0]['token']) {
            $view->generateWarning = $view->ts('plugin_onlineshop_voucherservice_msg-error-overwrite-single');
            $view->settings['Original Token'] = $this->getCodes()[0];
        }

        if ($codes = $this->getCodes()) {
            $view->paginator = Zend_Paginator::factory($codes);
            $view->count = sizeof($codes);
        }

        $view->settings = [
            $view->ts('plugin_onlineshop_voucherservice_settings-token') => $this->getConfiguration()->getToken(),
            $view->ts('plugin_onlineshop_voucherservice_settings-max-usages') => $this->getConfiguration()->getUsages(),
        ];

        $statisticUsagePeriod = 30;
        if(isset($params['statisticUsagePeriod'])){
            $statisticUsagePeriod = $params['statisticUsagePeriod'];
        }
        $view->statistics = $this->getStatistics($statisticUsagePeriod);

        return $this->template;
    }

    /**
     * @return int
     */
    public function getFinalTokenLength()
    {
        return strlen($this->configuration->getToken());
    }

    public function insertOrUpdateVoucherSeries()
    {
        $db = \Pimcore\Resource::get();
        try {
            $query =
                'INSERT INTO ' . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . '(token,length,voucherSeriesId) VALUES (?,?,?)
                    ON DUPLICATE KEY UPDATE token = ?, length = ?';

            $db->query($query, [trim($this->configuration->getToken()), $this->getFinalTokenLength(), $this->getSeriesId(), trim($this->configuration->getToken()), $this->getFinalTokenLength()]);
        } catch (Exception $e) {
            return ['error' => 'Something went wrong.']; //TODO Error
        }
    }

    /**
     * @param null|array $params
     * @return array|bool
     */
    public function getCodes($params = null)
    {
        return OnlineShop_Framework_VoucherService_Token_List::getCodes($this->seriesId, $params);
    }

    protected function prepareUsageStatisticData(&$data, $usagePeriod){
        $now = new DateTime("NOW");
        $periodData = [];
        for ($i = $usagePeriod; $i > 0; $i--) {
            $index = $now->format("Y-m-d");
            $periodData[$index] = isset($data[$index]) ? $data[$index] : 0;
            $now->modify('-1 day');
        }
        $data = $periodData;
    }

    /**
     * @return array
     */
    public function getStatistics($usagePeriod = null)
    {
        $overallCount = $this->configuration->getUsages();
        $usageCount = OnlineShop_Framework_VoucherService_Token::getByCode($this->configuration->getToken())->getUsages();
        $reservedTokenCount = OnlineShop_Framework_VoucherService_Token_List::getCountByReservation($this->seriesId);

        $usage = OnlineShop_Framework_VoucherService_Statistic::getBySeriesId($this->seriesId, $usagePeriod);
        $this->prepareUsageStatisticData($usage, $usagePeriod);

        return [
            'overallCount' => $overallCount,
            'usageCount' => $usageCount,
            'freeCount' => $overallCount - $usageCount - $reservedTokenCount,
            'reservedCount' => $reservedTokenCount,
            'usage' => $usage
        ];
    }

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool
     */
    public function reserveToken($code, \OnlineShop\Framework\CartManager\ICart $cart)
    {
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($code)) {
            if (OnlineShop_Framework_VoucherService_Reservation::create($code, $cart)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @param \OnlineShop\Framework\Model\AbstractOrder $order
     *
     * @return bool|\Pimcore\Model\Object\OnlineShopVoucherToken
     */
    public function applyToken($code, \OnlineShop\Framework\CartManager\ICart $cart, \OnlineShop\Framework\Model\AbstractOrder $order)
    {
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($code)) {
            if ($token->check($this->configuration->getUsages(), true)) {
                if ($token->apply()) {
                    $orderToken = \Pimcore\Model\Object\OnlineShopVoucherToken::getByToken($code, 1);
                    if(!$orderToken instanceof \Pimcore\Model\Object\OnlineShopVoucherToken) {
                        $orderToken = new \Pimcore\Model\Object\OnlineShopVoucherToken();
                        $orderToken->setTokenId($token->getId());
                        $orderToken->setToken($token->getToken());
                        $series = \Pimcore\Model\Object\OnlineShopVoucherSeries::getById($token->getVoucherSeriesId());
                        $orderToken->setVoucherSeries($series);
                        $orderToken->setParent($series);        // TODO set correct parent for applied tokens
                        $orderToken->setKey(\Pimcore\File::getValidFilename($token->getToken()));
                        $orderToken->setPublished(1);
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
     * @param \Pimcore\Model\Object\OnlineShopVoucherToken $tokenObject
     * @param \OnlineShop\Framework\Model\AbstractOrder $order
     * @return bool
     */
    public function removeAppliedTokenFromOrder(\Pimcore\Model\Object\OnlineShopVoucherToken $tokenObject, \OnlineShop\Framework\Model\AbstractOrder $order)
    {
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($tokenObject->getToken())) {
            return $token->unuse();
        }
        return false;
    }

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool
     */
    public function releaseToken($code, \OnlineShop\Framework\CartManager\ICart $cart)
    {
        return OnlineShop_Framework_VoucherService_Reservation::releaseToken($code, $cart);
    }

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool
     */
    public function checkToken($code, \OnlineShop\Framework\CartManager\ICart $cart)
    {
        parent::checkToken($code, $cart);
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($code)) {
            if ($token->check($this->configuration->getUsages())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Pimcore\Model\Object\Fieldcollection\Data\VoucherTokenTypeSingle
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param \Pimcore\Model\Object\Fieldcollection\Data\VoucherTokenTypeSingle $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return mixed
     */
    public function getSeriesId()
    {
        return $this->seriesId;
    }

    /**
     * @param mixed $seriesId
     */
    public function setSeriesId($seriesId)
    {
        $this->seriesId = $seriesId;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

}