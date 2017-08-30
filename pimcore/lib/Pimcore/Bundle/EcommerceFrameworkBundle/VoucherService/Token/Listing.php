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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token;

use Zend\Paginator\Adapter\AdapterInterface;
use Zend\Paginator\AdapterAggregateInterface;

class Listing extends \Pimcore\Model\Listing\AbstractListing implements \Zend_Paginator_Adapter_Interface, \Zend_Paginator_AdapterAggregate, \Iterator, AdapterInterface, AdapterAggregateInterface
{
    public $tokens;

    /**
     * @param  $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        if ($key == 'id' || $key == 'token' || $key == 'series_id' || $key == 'usages' || $key == 'timestamp') {
            return true;
        }

        return false;
    }

    /**
     * @param $seriesId
     * @param array $filter
     *
     * @throws \Exception
     */
    public function setFilterConditions($seriesId, $filter = [])
    {
        if (isset($seriesId)) {
            $this->addConditionParam('voucherSeriesId = ?', $seriesId);
        } else {
            throw new \Exception('Unable to load series tokens: no VoucherSeriesId given.', 100);
        }

        if (sizeof($filter)) {
            if (!empty($filter['token'])) {
                $this->addConditionParam('token LIKE ?', '%' . $filter['token'] . '%');
            }

            if (isset($filter['usages']) && $filter['usages'] !== '') {
                $this->addConditionParam('usages = ?', (int) $filter['usages']);
            }

            if (!empty($filter['length'])) {
                $this->addConditionParam('length = ?', $filter['length']);
            }

            if ($filter['creation_to'] && $filter['creation_from']) {
                $this->addConditionParam("Date(timestamp) BETWEEN STR_TO_DATE(?,'%Y-%m-%d')", $filter['creation_from']);
                $this->addConditionParam("STR_TO_DATE(?,'%Y-%m-%d')", $filter['creation_to']);
            } else {
                if ($filter['creation_from']) {
                    $this->addConditionParam("DATE(timestamp) >= STR_TO_DATE(?,'%Y-%m-%d')", $filter['creation_from']);
                }
                if ($filter['creation_to']) {
                    $this->addConditionParam("DATE(timestamp) <= STR_TO_DATE(?,'%Y-%m-%d')", $filter['creation_to']);
                }
            }

            if ($this->isValidOrderKey($filter['sort_criteria'])) {
                $this->setOrderKey($filter['sort_criteria']);
            } else {
                $this->setOrderKey('timestamp');
            }

            if ($filter['sort_order'] == 'ASC') {
                $this->setOrder('ASC');
            } else {
                $this->setOrder('DESC');
            }
        }
    }

    public static function getBySeriesId($seriesId)
    {
        try {
            $config = new self();
            $config->setCondition('series_id', $seriesId);
            $config->getDao()->load();

            return $config;
        } catch (\Exception $ex) {
            //            Logger::debug($ex->getMessage());
            return false;
        }
    }

    /**
     * @return array
     */
    public function getTokenList()
    {
        if (empty($this->tokens)) {
            $this->load();
        }

        return $this->tokens;
    }

    public static function getCodes($seriesId, $params)
    {
        $db = \Pimcore\Db::get();
        $query = 'SELECT * FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . ' WHERE voucherSeriesId = ?';
        $queryParams[] = $seriesId;

        if (!empty($params['token'])) {
            $query .= ' AND token LIKE ?';
            $queryParams[] = '%' . $params['token'] . '%';
        }
        if (!empty($params['usages'])) {
            $query .= ' AND usages = ? ';
            $queryParams[] = $params['usages'];
        }
        if (!empty($params['length'])) {
            $query .= ' AND length = ? ';
            $queryParams[] = $params['length'];
        }
        if (!empty($params['creation_to']) && isset($params['creation_from'])) {
            $from = $db->quote($params['creation_from']);
            $to = $db->quote($params['creation_to']);
            $query .= ' AND timestamp BETWEEN STR_TO_DATE(' . $from . ",'%Y-%m-%d') AND STR_TO_DATE(" . $to . ",'%Y-%m-%d')";
        } else {
            if (!empty($params['creation_from'])) {
                $param = $db->quote($params['creation_from']);
                $query .= ' AND timestamp >= STR_TO_DATE(' . $param . ",'%Y-%m-%d')";
            }
            if (!empty($params['creation_to'])) {
                $param = $db->quote($params['creation_to']);
                $query .= ' AND timestamp <= STR_TO_DATE(' . $param . ",'%Y-%m-%d') + INTERVAL 1 DAY";
            }
        }

        $tmp = new self();
        if ($tmp->isValidOrderKey($params['sort_criteria'])) {
            $query .= ' ORDER BY ' . $params['sort_criteria'];
        } else {
            $query .= ' ORDER BY timestamp';
        }

        if ($params['sort_order'] == 'ASC') {
            $query .= ' ASC';
        } else {
            $query .= ' DESC';
        }

        try {
            $codes = $db->fetchAll($query, array_values($queryParams));
        } catch (\Exception $e) {
            return false;
        }

        return $codes;
    }

    public static function getCountByUsages($usages = 1, $seriesId = null)
    {
        $query = 'SELECT COUNT(*) as count FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . ' WHERE usages >= ? ';
        $params[] = $usages;
        if (isset($seriesId)) {
            $query .= ' AND voucherSeriesId = ?';
            $params[] = $seriesId;
        }

        $db = \Pimcore\Db::get();
        try {
            return $db->fetchOne($query, $params);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getCountBySeriesId($seriesId)
    {
        $query = 'SELECT COUNT(*) as count FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . ' WHERE voucherSeriesId = ?';
        $params[] = $seriesId;

        $db = \Pimcore\Db::get();
        try {
            return $db->fetchOne($query, $params);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getCountByReservation($seriesId = null)
    {
        $query = 'SELECT COUNT(t.id) FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . ' as t
            INNER JOIN ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao::TABLE_NAME . ' as r ON t.token = r.token';
        if (isset($seriesId)) {
            $query .= ' WHERE voucherSeriesId = ?';
            $params[] = $seriesId;
        }

        $db = \Pimcore\Db::get();
        try {
            return $db->fetchOne($query, $params);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $length
     * @param null|string $seriesId
     *
     * @return null|string
     */
    public static function getCountByLength($length, $seriesId = null)
    {
        $query = 'SELECT COUNT(*) as count FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . ' WHERE length = ?';
        $params = [$length];
        if (isset($seriesId)) {
            $query .= ' AND voucherSeriesId = ?';
            $params[] = $seriesId;
        }

        $db = \Pimcore\Db::get();

        try {
            $result = $db->fetchOne($query, $params);

            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Use with care, cleans all tokens of a series and the dependent
     * reservations.
     *
     * @param string $seriesId
     *
     * @return bool
     */
    public static function cleanUpAllTokens($seriesId)
    {
        return self::cleanUpTokens($seriesId);
    }

    /**
     * @param string $seriesId
     * @param array $filter
     * @param int $maxUsages
     *
     * @return bool
     */
    public static function cleanUpTokens($seriesId, $filter = [], $maxUsages = 1)
    {
        $db = \Pimcore\Db::get();

        $reservationsQuery = 'DELETE r FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . ' AS t
                        JOIN ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao::TABLE_NAME . ' AS r
                        ON t.token = r.token
                        WHERE t.voucherSeriesId = ?';

        $tokensQuery = 'DELETE t FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . ' AS t WHERE t.voucherSeriesId = ?';
        $params[] = $seriesId;

        $queryParts = [];

        if (isset($filter['usage'])) {
            if ($filter['usage'] == 'used') {
                $queryParts[] = 't.usages >= ' . $maxUsages;
            } elseif ($filter['usage'] == 'unused') {
                $queryParts[] = 't.usages = 0';
            } elseif ($filter['usage'] == 'both') {
                $queryParts[] = 't.usages >= 0';
            }
        }

        if (isset($filter['olderThan'])) {
            $param = $db->quote($filter['olderThan']);
            $queryParts[] = 't.timestamp < STR_TO_DATE(' . $param . ",'%Y-%m-%d')";
        }

        if (sizeof($queryParts) == 1) {
            $reservationsQuery = $reservationsQuery . ' AND ' . $queryParts[0];
            $tokensQuery = $tokensQuery . ' AND ' . $queryParts[0];
        } elseif (sizeof($queryParts) > 1) {
            $reservationsQuery = $reservationsQuery . ' AND (' . implode(' AND ', $queryParts) . ')';
            $tokensQuery = $tokensQuery . ' AND (' . implode(' AND ', $queryParts) . ')';
        }

        $db->beginTransaction();
        try {
            $db->executeQuery($reservationsQuery, $params);
            $db->executeQuery($tokensQuery, $params);
            $db->commit();

            return true;
        } catch (\Exception $e) {
            $db->rollBack();

            return false;
        }
    }

    /**
     * @param $codes
     *
     * @return bool
     */
    public static function tokensExist($codes)
    {
        $db = \Pimcore\Db::get();

        if (!is_array($codes)) {
            $token = [$codes];
        }

        $query = 'SELECT EXISTS(SELECT id FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . " WHERE token IN ('" . implode("', '", $codes) . "'))";

        $result = $db->fetchOne($query);

        if ($result == 0) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param mixed $tokens
     */
    public function setTokens($tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @return \Pimcore\Model\DataObject\Listing|AdapterInterface
     */
    public function getPaginatorAdapter()
    {
        return $this;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->getTokens();
        $var = current($this->tokens);

        return $var;
    }

    /**
     * @return mixed|void
     */
    public function next()
    {
        $this->getTokens();
        $var = next($this->tokens);

        return $var;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        $this->getTOkens();
        $var = key($this->tokens);

        return $var;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $this->getTokens();
        $var = $this->current() !== false;

        return $var;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->getTokens();
        reset($this->tokens);
    }

    /**
     * Returns an collection of items for a page.
     *
     * @param  int $offset Page offset
     * @param  int $itemCountPerPage Number of items per page
     *
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->load();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return $this->getTotalCount();
    }
}
