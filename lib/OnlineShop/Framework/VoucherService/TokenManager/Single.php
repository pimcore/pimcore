<?php

class OnlineShop_Framework_VoucherService_TokenManager_Single implements OnlineShop_Framework_VoucherService_ITokenManager
{

    public
        $configuration,
        $seriesId;

    protected $template = "voucher/voucher-code-tab-single.php";

    public function __construct(OnlineShop_Framework_AbstractVoucherTokenType $configuration)
    {
        if ($configuration instanceof \Pimcore\Model\Object\Fieldcollection\Data\VoucherTokenTypeSingle) {
            $this->configuration = $configuration;
            $this->seriesId = $configuration->getObject()->getId();
        } else {
            throw new Exception("Invalid Configuration Class.");
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
    public function cleanUpCodes()
    {
    }


    public function cleanupReservations($duration = 0)
    {

    }

    public function prepareConfigurationView($view, $params)
    {
        if ($this->getConfiguration()->getToken() != $this->getCodes()[0]['token']) {
            $view->generateWarning = "You may overwrite your single token with this operation, please check your settings.";  //TODO translate
            $view->settings['Original Token'] = $this->getCodes()[0];
        }

        if ($codes = $this->getCodes()) {
            $view->paginator = Zend_Paginator::factory($codes);
            $view->count = sizeof($codes);
        }

        $view->settings = [
            'Token' => $this->getConfiguration()->getToken(),
            'Max Usages' => $this->getConfiguration()->getUsages(),
        ];

        $view->statistics = $this->getStatistics();

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

    /**
     * @return array
     */
    public function getStatistics()
    {
        $overallCount = $this->configuration->getUsages();
        $usageCount = OnlineShop_Framework_VoucherService_Token::getByCode($this->configuration->getToken())->getUsages();
        $reservedTokenCount = OnlineShop_Framework_VoucherService_Token_List::getCountByReservation($this->seriesId);

        $usage = OnlineShop_Framework_VoucherService_Statistic::getBySeriesId($this->seriesId);

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
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function reserveToken($code, OnlineShop_Framework_ICart $cart)
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
     * @param OnlineShop_Framework_ICart $cart
     * @param OnlineShop_Framework_AbstractOrder $order
     *
     * @return bool|Object_OnlineShopVoucherToken
     */
    public function applyToken($code, OnlineShop_Framework_ICart $cart, OnlineShop_Framework_AbstractOrder $order)
    {
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($code)) {
            if ($token->isAvailable($this->configuration->getUsages(), true)) {
                if ($token->apply()) {
                    $orderToken = new Object_OnlineShopVoucherToken();
                    $orderToken->setTokenId($token->getId());
                    $orderToken->setToken($token->getToken());
                    $series = Object_OnlineShopVoucherSeries::getById($token->getVoucherSeriesId());
                    $orderToken->setVoucherSeries([$series]);
                    $orderToken->setParent($series);        // TODO set correct parent for applied tokens
                    $orderToken->setKey(\Pimcore\File::getValidFilename($token->getToken() . "_" . substr(uniqid(), 8)));
                    $orderToken->setPublished(1);
                    $orderToken->save();

                    return $orderToken;
                }
            }
        }
        return false;
    }

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function releaseToken($code, OnlineShop_Framework_ICart $cart)
    {
        return OnlineShop_Framework_VoucherService_Reservation::releaseToken($code, $cart);
    }

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function checkToken($code, OnlineShop_Framework_ICart $cart)
    {
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($code)) {
            if ($token->isAvailable($this->configuration->getUsages())) {
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