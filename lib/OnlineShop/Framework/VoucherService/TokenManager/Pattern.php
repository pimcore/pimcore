<?php
// TODO Select Queries in Blöcke aufteilen.  http://onlineshop.plugins.elements.pm/plugin/OnlineShop/voucher/test

/**
 * Class OnlineShop_Framework_VoucherService_TokenManager_Pattern
 */
class OnlineShop_Framework_VoucherService_TokenManager_Pattern implements OnlineShop_Framework_VoucherService_ITokenManager
{
    /**
     * @var float Maximale Wahrscheinlich beim Einfügen einen Doppelten Eintrag zu treffen i.e. Wahrscheinlichkeit einen Code zu erraten.
     */
    const MAX_PROBABILITY = 0.005;

    protected $characterPools = [
        'alphaNumeric' => "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ",
        'numeric' => "123456789",
        'alpha' => "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ"
    ];

    protected $template = "voucher/voucher-code-tab-pattern.php";

    public
        $configuration,
        $seriesId;

    public function __construct(OnlineShop_Framework_AbstractVoucherTokenType $configuration)
    {
        if ($configuration instanceof \Pimcore\Model\Object\Fieldcollection\Data\VoucherTokenTypePattern) {
            $this->configuration = $configuration;
            $this->seriesId = $configuration->getObject()->getId();
        } else {
            throw new Exception("Invalid Configuration Class.");
        }
    }

    protected function characterPoolExists($poolName)
    {
        return array_key_exists($poolName, $this->getCharacterPools());
    }


    public function getInsertProbability()
    {
        $maxCount = $this->getMaxCount();

        $dbCount = OnlineShop_Framework_VoucherService_Token_List::getCountByLength($this->getFinalTokenLength(), $this->seriesId);
        if ($dbCount !== null && $maxCount >= 0) {
            return ((int)$dbCount + $this->configuration->getCount()) / $maxCount;
        }
        return 1;
    }

    /**
     * @return bool
     */
    public function isValidSetting()
    {
        if ($this->characterPoolExists($this->configuration->getCharacterType()) && $this->configuration->getLength() > 0) {
            return true;
        }
        return false;
    }

    protected function isValidGeneration()
    {
        if (!$this->isValidSetting()) {
            return false;
        }
        $insertProbability = $this->getInsertProbability();
        if ($insertProbability <= self::MAX_PROBABILITY) {
            return true;
        }
        return false;
    }

    public function getCharacterPool()
    {
        return $this->characterPools[$this->configuration->getCharacterType()];
    }

    /**
     * @return number
     */
    protected
    function getMaxCount()
    {
        $count = strlen($this->getCharacterPool());
        return pow($count, $this->configuration->getLength());
    }

    protected function generateCode()
    {
        $key = "";
        $charPool = $this->getCharacterPool();
        $size = strlen($charPool);
        for ($i = 0; $i < $this->configuration->getLength(); $i++) {
            $rand = mt_rand(0, $size - 1);
            $key .= $charPool[$rand];
        }
        return $key;
    }

    /**
     * @param string $code Generated Code.
     * @return string Formated Code.
     */
    protected function formatCode($code)
    {
        $separator = $this->configuration->getSeparator();
        $prefix = $this->getConfiguration()->getPrefix();
        if (!empty($separator)) {
            if (!empty($prefix)) {
                $code = $this->configuration->prefix . $separator . implode($separator, str_split($code, $this->configuration->getSeparatorCount()));
            } else {
                $code = implode($separator, str_split($code, $this->configuration->getSeparatorCount()));
            }

        } else {
            $code = $this->configuration->prefix . $code;
        }
        return $code;
    }


    /**
     * @param   int $length
     * @return  int
     */
    public function getFinalTokenLength()
    {
        $separatorCount = $this->configuration->getSeparatorCount();
        $separator = $this->configuration->getSeparator();
        $prefix = $this->configuration->getPrefix();
        if (!empty($separator)) {
            if (!empty($prefix)) {
                return strlen($this->configuration->prefix) + 1 + floor($this->configuration->getLength() / $separatorCount) + $this->configuration->getLength();
            }
            return floor($this->configuration->getLength() / $separatorCount) + $this->configuration->getLength();
        }

        return strlen($this->configuration->prefix) + $this->configuration->getLength();
    }

    /**
     * Checks whether a token is in the an array of tokens, the token is the key of the array.
     *
     * @param string|array $tokens One or more tokens.
     * @param array $cTokens Array of tokens.
     * @return bool
     */
    protected function tokenExists($tokens, $cTokens)
    {
        if (!is_array($tokens)) {
            $tokens = [$tokens];
        }
        $check = array_intersect_key($tokens, $cTokens);

        if (!empty($check)) {
            return true;
        }
    }

    public function getExampleToken()
    {
        return $this->formatCode($this->generateCode());
    }

    protected function buildInsertQuery($insertTokens)
    {
        $query = 'INSERT INTO ' . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . '(token,length,voucherSeriesId) ';
        $finalLength = $this->getFinalTokenLength();

        if (sizeof($insertTokens) > 0) {
            foreach ($insertTokens as $token) {
                $insertParts[] =
                    "('" .
                    $token .
                    "'," .
                    $finalLength .
                    "," .
                    $this->seriesId .
                    ")";
            }
        }
        return $query .= "VALUES " . implode(",", $insertParts);
    }

    public function generateCodes($getInsertQuery = false)
    {
        // Size of one segment of tokens to check against the db.
        $tokenCheckStep = ceil($this->configuration->getCount() / 250);

        if ($this->isValidGeneration()) {
            $finalTokenLength = $this->getFinalTokenLength();
            // Check if a max_packet_size Error is possible
            $possibleMaxQuerySizeError = ($finalTokenLength * $this->configuration->getCount() / 1024 / 1024) > 15;
            // Return Query
            $insertQueryArray = false;

            // Tokens of one Insert Query
            $insertTokens = [];
            // Tokens of all Insert Queries together
            $insertCheckTokens = [];
            // Tokens of one segment of tokens to check against if they already exist in the db
            $checkTokens = [];

            // Count for all tokens to insert into db
            $insertCount = 0;
            // Count for tokens to check in db in on segment
            $checkTokenCount = 0;

            /**
             * Create unique tokens
             */
            while ($insertCount < $this->configuration->getCount()) {
                // Considerations for last Couple of tokens, so that the amount of overall tokens is correct.
                if ($this->configuration->getCount() > ($insertCount + $checkTokenCount)) {
                    $token = $this->formatCode($this->generateCode());
                    // If the key already exists in the current checkTokens Segment,
                    // do not increase the checkTokensCount
                    if (!array_key_exists($token, $checkTokens)) {
                        $checkTokens[$token] = $token;
                        $checkTokenCount++;
                    }
                } else {
                    $checkTokenCount++;
                }

                // Check the temp array checkTokens if the just generated token already exists.
                // If so, unset the last token and decrease the count for the array of tokens to check
                if ($this->tokenExists($checkTokens, $insertCheckTokens)) {
                    $checkTokenCount--;
                    unset($checkTokens[$token]);
                    // Check if the length of the checkTokens Array matches the defined step range
                    // so the the checkTokens get matched against the database.
                } elseif ($checkTokenCount == $tokenCheckStep) {
                    // Check if any of the tokens in the temporary array checkTokens already exists,
                    // if not so, merge the checkTokens array with the array of tokens to insert and
                    // increase the overall count by the length of the checkArray i.e. the checkTokenStep
                    if (!OnlineShop_Framework_VoucherService_Token_List::tokensExist($checkTokens)) {
                        $insertTokens = array_merge($insertTokens, $checkTokens);
                        $insertCount += $tokenCheckStep;
                    }
                    $checkTokenCount = 0;
                    $checkTokens = [];

                    // If an max_package_size error is possible build a new insert query.
                    if ($possibleMaxQuerySizeError && $getInsertQuery) {
                        if (($insertCount * $finalTokenLength / 1024 / 1024) > 15) {
                            $insertQueryArray[] = $this->buildInsertQuery($insertTokens);
                            $insertCheckTokens = array_merge($insertTokens, $insertCheckTokens);
                            $insertTokens = [];
                        }
                    } else {
                        // If no Error is possible or insert query needed, the overall tokens
                        // are the insert tokens of the current query, because there will be only
                        // one or no query.
                        $insertCheckTokens = $insertTokens;
                    }

                }
            }

            /**
             * Submit insert query.
             */
            if ($getInsertQuery) {
                if (sizeof($insertTokens)) {
                    $insertQueryArray[] = $this->buildInsertQuery($insertTokens);
                }
                return $insertQueryArray;
            } else {
                return $insertTokens;
            }
        }
        return false;
    }

    public function insertOrUpdateVoucherSeries()
    {
        $db = \Pimcore\Resource::get();
        try {
            $generatedInsertQueries = $this->generateCodes(true);

            if (is_array($generatedInsertQueries)) {
                foreach ($generatedInsertQueries as $query) {
                    $db->query($query);
                }
            } else {
                $db->query($generatedInsertQueries);
            }


        } catch (Exception $e) {
//            var_dump($e);
//            \Pimcore\Log\Simple::log('VoucherSystem', $e);
            return ['error' => 'Token generation not possible, please adjust your parameters in the edit tab.']; //TODO translate

        }
    }

    /**
     * @param array|null $params
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
        $overallCount = OnlineShop_Framework_VoucherService_Token_List::getCountBySeriesId($this->seriesId);
        $usageCount = OnlineShop_Framework_VoucherService_Token_List::getCountByUsages(1, $this->seriesId);
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
     * @param array $params Associative with the indices: "used", "unused" and "olderThan".
     * @return bool
     */
    public function cleanUpCodes($params = [])
    {
        if (OnlineShop_Framework_VoucherService_Token_List::cleanUpTokens($this->seriesId, $params)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool|int
     */
    public function checkToken($code, OnlineShop_Framework_ICart $cart)
    {
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($code)) {
            if (!$token->isUsed()) {
                if (!$token->isReserved()) {
                    return true;
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
     * @return bool
     */
    public function releaseToken($code, OnlineShop_Framework_ICart $cart)
    {
        return OnlineShop_Framework_VoucherService_Reservation::releaseToken($code);
    }

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @param OnlineShop_Framework_AbstractOrder $order
     *
     * @return bool|OnlineShop_Framework_VoucherService_Token
     */
    public function applyToken($code, OnlineShop_Framework_ICart $cart, OnlineShop_Framework_AbstractOrder $order)
    {
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($code)) {
            if (!$token->isUsed()) {
                if ($token->apply()) {
                    $orderToken = new Object_OnlineShopVoucherToken();
                    $orderToken->setTokenId($token->getId());
                    $orderToken->setToken($token->getToken());
                    $series = Object_OnlineShopVoucherSeries::getById($token->getVoucherSeriesId());
                    $orderToken->setVoucherSeries([$series]);
                    $orderToken->setParent($series);        // TODO set correct parent for applied tokens
                    $orderToken->setKey(\Pimcore\File::getValidFilename($token->getToken()));
                    $orderToken->setPublished(1);
                    $orderToken->save();

                    return $orderToken;
                }
            }
        }

        return false;
    }

    /**
     * @param $view
     * @param array $params
     * @return string
     * @throws Zend_Paginator_Exception
     */
    public function prepareConfigurationView($view, $params)
    {
        if ($codes = $this->getCodes($params)) {
            $view->paginator = Zend_Paginator::factory($codes);
            $view->count = sizeof($codes);
        }else{
            $view->error = "No Tokens found.";  // TODO
        }

        $view->error = $params['error'];

        $view->settings = [
            'Amount' => $this->getConfiguration()->getCount(),
            'Prefix' => $this->getConfiguration()->getPrefix(),
            'Length' => $this->getConfiguration()->getLength(),
            'Example' => $this->getExampleToken(),
        ];

        $view->statistics = $this->getStatistics();

        return $this->template;
    }

    public function cleanUpReservations($duration = 0)
    {
        OnlineShop_Framework_VoucherService_Reservation::cleanUpReservations($duration);
    }

    /**
     * @return \Pimcore\Model\Object\Fieldcollection\Data\VoucherTokenTypePattern
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param \Pimcore\Model\Object\Fieldcollection\Data\VoucherTokenTypePattern $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return array
     */
    public function getCharacterPools()
    {
        return $this->characterPools;
    }

    /**
     * @param array $characterPools
     */
    public function setCharacterPools($characterPools)
    {
        $this->characterPools = $characterPools;
    }

    /**
     * @param array $pool Associative Array - the key represents the name, the value the characters of the character-pool. i.e.:"['numeric'=>'12345']"
     */
    public function addCharacterPool($pool)
    {
        if (is_array($pool)) {
            $this->characterPools[] = $pool;
        }
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @param mixed $seriesId
     */
    public function setSeriesId($seriesId)
    {
        $this->seriesId = $seriesId;
    }

    /**
     * @return mixed
     */
    public function getSeriesId()
    {
        return $this->seriesId;
    }


}