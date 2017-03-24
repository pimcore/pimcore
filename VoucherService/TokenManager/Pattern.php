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

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\VoucherServiceException;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractVoucherTokenType;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Reservation;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Statistic;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Token;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Token\Listing;
use Pimcore\Model\Object\Fieldcollection\Data\VoucherTokenTypePattern;
use Pimcore\Model\Object\OnlineShopVoucherToken;
use Zend\Paginator\Paginator;

/**
 * Class Pattern
 */
class Pattern extends AbstractTokenManager implements IExportableTokenManager
{
    /* @var float Max probability to hit a duplicate entry on insertion e.g. to guess a code  */

    const MAX_PROBABILITY = 0.005;

    protected $template;

    protected $characterPools = [
        'alphaNumeric' => "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ",
        'numeric' => "123456789",
        'alpha' => "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ"
    ];

    public function __construct(AbstractVoucherTokenType $configuration)
    {
        parent::__construct($configuration);
        if ($configuration instanceof VoucherTokenTypePattern) {
            $this->template = "PimcoreEcommerceFrameworkBundle:Voucher:voucherCodeTabPattern.html.php";
        } else {
            throw new VoucherServiceException("Invalid Configuration Class for Type VoucherTokenTypePattern.");
        }
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

    /**
     * @param array|null $filter Associative with the indices: "usage" and "olderThan".
     * @return bool
     */
    public function cleanUpCodes($filter = [])
    {
        return Listing::cleanUpTokens($this->seriesId, $filter);
    }

    /**
     * @param string $code
     * @param ICart $cart
     * @throws VoucherServiceException
     * @return bool|int
     */
    public function checkToken($code, ICart $cart)
    {
        parent::checkToken($code, $cart);
        if ($token = Token::getByCode($code)) {
            if ($token->isUsed()) {
                throw new VoucherServiceException('Token has already been used.', 1);
            }
            if ($token->isReserved()) {
                throw new VoucherServiceException('Token has already been reserved.', 2);
            }
        }

        return true;
    }

    /**
     * @param string $code
     * @param ICart $cart
     * @throws VoucherServiceException
     * @return bool
     */
    public function reserveToken($code, ICart $cart)
    {
        if ($token = Token::getByCode($code)) {
            if (Reservation::create($code, $cart)) {
                return true;
            } else {
                throw new VoucherServiceException("Token Reservation not possible.", 3);
            }
        }
        throw new VoucherServiceException("No Token for this code exists.", 4);
    }

    /**
     * @param string $code
     * @param ICart $cart
     * @param AbstractOrder $order
     *
     * @throws VoucherServiceException
     *
     * @return bool|OnlineShopVoucherToken
     */
    public function applyToken($code, ICart $cart, AbstractOrder $order)
    {
        if ($token = Token::getByCode($code)) {
            if ($token->isUsed()) {
                throw new VoucherServiceException('Token has already been used.', 1);
            }
            if ($token->apply()) {
                $orderToken = new OnlineShopVoucherToken();
                $orderToken->setTokenId($token->getId());
                $orderToken->setToken($token->getToken());
                $series = \Pimcore\Model\Object\OnlineShopVoucherSeries::getById($token->getVoucherSeriesId());
                $orderToken->setVoucherSeries($series);
                $orderToken->setParent($series);
                $orderToken->setKey(\Pimcore\File::getValidFilename($token->getToken()));
                $orderToken->setPublished(1);
                $orderToken->save();

                return $orderToken;
            }
        }

        return false;
    }


    /**
     * cleans up the token usage and the ordered token object if necessary
     *
     * @param OnlineShopVoucherToken $tokenObject
     * @param AbstractOrder $order
     * @return bool
     */
    public function removeAppliedTokenFromOrder(OnlineShopVoucherToken $tokenObject, AbstractOrder $order)
    {
        if ($token = Token::getByCode($tokenObject->getToken())) {
                $token->unuse();
            $tokenObject->delete();
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param string $code
     * @param ICart $cart
     * @return bool
     */
    public function releaseToken($code, ICart $cart)
    {
        return Reservation::releaseToken($code);
    }

    /**
     * @param array|null $filter
     * @return array|bool
     */
    public function getCodes($filter = null)
    {
        return Token\Listing::getCodes($this->seriesId, $filter);
    }


    /**
     * @return array
     */
    public function getStatistics($usagePeriod = null)
    {
        $overallCount = Token\Listing::getCountBySeriesId($this->seriesId);
        $usageCount = Token\Listing::getCountByUsages(1, $this->seriesId);
        $reservedTokenCount = Token\Listing::getCountByReservation($this->seriesId);

        $usage = Statistic::getBySeriesId($this->seriesId, $usagePeriod);
        if (is_array($usage)) {
            $this->prepareUsageStatisticData($usage, $usagePeriod);
        }

        return [
            'overallCount' => $overallCount,
            'usageCount' => $usageCount,
            'freeCount' => $overallCount - $usageCount - $reservedTokenCount,
            'reservedCount' => $reservedTokenCount,
            'usage' => $usage
        ];
    }

    /**
     * Generates Codes and an according Insert Query, if the MAX_PACKAGE_SIZE
     * may be reached several queries are generated.
     *
     * @return array|bool
     */
    public function insertOrUpdateVoucherSeries()
    {
        $db = \Pimcore\Db::get();
        try {
            $codeSets = $this->generateCodes();

            if (is_array($codeSets)) {
                foreach ($codeSets as $query) {
                    $db->query($this->buildInsertQuery($query));
                }
            } else {
                $db->query($this->buildInsertQuery($codeSets));
            }
            return $codeSets;

        } catch (\Exception $e) {
//            var_dump($e);
//            \Pimcore\Log\Simple::log('VoucherSystem', $e);
            return false;
        }
    }

    /**
     * Gets the final length of the token, incl.
     * prefix and separators.
     *
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
     * Calculates the probability to hit an existing value on a token generation.
     *
     * @return float
     */
    public function getInsertProbability()
    {
        $maxCount = $this->getMaxCount();

        $dbCount = Token\Listing::getCountByLength($this->getFinalTokenLength(), $this->seriesId);

        if ($dbCount !== null && $maxCount >= 0) {
            return ((int)$dbCount + $this->configuration->getCount()) / $maxCount;
        }
        return 1.0;
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

    /**
     * Calculates the max possible amount of tokens for the specified character pool.
     *
     * @return number
     */
    protected function getMaxCount()
    {
        $count = strlen($this->getCharacterPool());
        return pow($count, $this->configuration->getLength());
    }

    /**
     * Generates a single code.
     *
     * @return string
     */
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
     * Puts the code in the defined format. Incl. prefix and separators.
     *
     * @param string $code Generated Code.
     * @return string formated Code.
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

    /**
     * Builds an insert query for an array of tokens.
     *
     * @param $insertTokens
     * @return string
     */
    protected function buildInsertQuery($insertTokens)
    {
        $query = 'INSERT INTO ' . Token\Dao::TABLE_NAME . '(token,length,voucherSeriesId) ';
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
        return $query . "VALUES " . implode(",", $insertParts);
    }

    /**
     * Generates a set of unique tokens according to the given token settings.
     * Returns false if the generation is not possible, due to set insert
     * probability MAX_INSERT_PROBABILITY.
     *
     * @return array|bool
     */
    public function generateCodes()
    {
        // Size of one segment of tokens to check against the db.
        $tokenCheckStep = ceil($this->configuration->getCount() / 250);

        if ($this->isValidGeneration()) {
            $finalTokenLength = $this->getFinalTokenLength();
            // Check if a max_packet_size Error is possible
            $possibleMaxQuerySizeError = ($finalTokenLength * $this->configuration->getCount() / 1024 / 1024) > 15;
            // Return Query
            $resultTokenSet = false;
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


            // Create unique tokens
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
                    if (!Token\Listing::tokensExist($checkTokens)) {
                        $insertTokens = array_merge($insertTokens, $checkTokens);
                        $insertCount += $tokenCheckStep;
                    }
                    $checkTokenCount = 0;
                    $checkTokens = [];

                    // If an max_package_size error is possible build a new insert query.
                    if ($possibleMaxQuerySizeError) {
                        if (($insertCount * $finalTokenLength / 1024 / 1024) > 15) {
                            $resultTokenSet[] = $insertTokens;
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

            // If there are tokens to insert add them to query.
            if (sizeof($insertTokens)) {
                $resultTokenSet[] = $insertTokens;
            }
            return $resultTokenSet;
        }
        return false;
    }

    /**
     * Creates an array with the indices of days of the given usage period.
     *
     * @param array $data
     * @param $usagePeriod
     */
    protected function prepareUsageStatisticData(&$data, $usagePeriod)
    {
        $now = new \DateTime();
        $periodData = [];
        for ($i = $usagePeriod; $i > 0; $i--) {
            $index = $now->format("Y-m-d");
            $periodData[$index] = isset($data[$index]) ? $data[$index] : 0;
            $now->modify('-1 day');
        }
        $data = $periodData;
    }

    /**
     * Prepares the view and returns the according template for rendering.
     *
     * @param $viewParamsBag
     * @param array $params
     * @return string
     */
    public function prepareConfigurationView(&$viewParamsBag, $params)
    {
        $viewParamsBag['msg'] = [];

        $tokens = new Token\Listing();

        try {
            $tokens->setFilterConditions($params['id'], $params);
        } catch (\Exception $e) {
            $this->template = "PimcoreEcommerceFrameworkBundle:Voucher:voucherCodeTabError.html.php";
            $viewParamsBag['errors'][] = $e->getMessage() . " | Error-Code: " . $e->getCode();
        }

        if ($tokens) {

            $paginator = new Paginator($tokens);

            if ($params['tokensPerPage']) {
                $paginator->setItemCountPerPage((int)$params['tokensPerPage']);
            } else {
                $paginator->setItemCountPerPage(25);
            }

            $paginator->setCurrentPageNumber($params['page']);

            $viewParamsBag['paginator'] = $paginator;
            $viewParamsBag['count'] = sizeof($tokens);

        } else {
            $viewParamsBag['msg']['result'] = 'plugin_onlineshop_voucherservice_msg-error-token-noresult';
        }

        $viewParamsBag['msg']['error'] = $params['error'];
        $viewParamsBag['msg']['success'] = $params['success'];

        // Settings parsed via foreach in view -> key is translation
        $viewParamsBag['settings'] = [
            'plugin_onlineshop_voucherservice_settings-count' => $this->getConfiguration()->getCount(),
            'plugin_onlineshop_voucherservice_settings-prefix' => $this->getConfiguration()->getPrefix(),
            'plugin_onlineshop_voucherservice_settings-length' => $this->getConfiguration()->getLength(),
            'plugin_onlineshop_voucherservice_settings-exampletoken' => $this->getExampleToken(),
        ];

        $statisticUsagePeriod = 30;

        if (isset($params['statisticUsagePeriod'])) {
            $statisticUsagePeriod = $params['statisticUsagePeriod'];
        }

        $viewParamsBag['tokenLengths'] = $this->series->getExistingLengths();

        $viewParamsBag['statistics'] = $this->getStatistics($statisticUsagePeriod);

        return $this->template;
    }

    /**
     * Get data for export
     *
     * @param array $params
     * @return array
     * @throws \Exception
     */
    protected function getExportData(array $params)
    {
        $tokens = new Token\Listing();
        $tokens->setFilterConditions($params['id'], $params);

        $paginator = new Paginator($tokens);
        $paginator->setItemCountPerPage(-1);

        $data = [];

        /** @var Token $token */
        foreach ($paginator as $token) {
            $data[] = [
                'token'     => $token->getToken(),
                'usages'    => $token->getUsages(),
                'length'    => $token->getLength(),
                'timestamp' => $token->getTimestamp()
            ];
        }

        return $data;
    }

    /**
     * Removes reservations
     *
     * @param int $duration
     * @return bool
     */
    public function cleanUpReservations($duration = 0)
    {
        return Reservation::cleanUpReservations($duration, $this->seriesId);
    }

    /**
     * Checks whether an index for the given name parameter exists in
     * the character pool member array.
     *
     * @param $poolName
     * @return bool
     */
    protected function characterPoolExists($poolName)
    {
        return array_key_exists($poolName, $this->getCharacterPools());
    }

    /**
     * Generates and returns an example token to the given settings.
     *
     * @return string
     */
    public function getExampleToken()
    {
        return $this->formatCode($this->generateCode());
    }

    /**
     * Getters and Setters
     */

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

    public function getCharacterPool()
    {
        return $this->characterPools[$this->configuration->getCharacterType()];
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
