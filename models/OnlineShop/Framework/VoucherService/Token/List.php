<?php

class OnlineShop_Framework_VoucherService_Token_List extends \Pimcore\Model\Listing\AbstractListing
{

    public $tokens;

    /**
     * @param  $key
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        if ($key == "id" || $key == "token" || $key == "series_id" || $key == "usages" || $key == "timestamp") {
            return true;
        }
        return false;
    }

    public static function getBySeriesId($seriesId)
    {
        try {
            $config = new self();
            $config->setCondition('series_id', $seriesId);
            $config->getResource()->load();
            return $config;
        } catch (Exception $ex) {
//            Logger::debug($ex->getMessage());
            return false;
        }
    }

    public static function getCodes($seriesId, $params)
    {

        $db = \Pimcore\Resource::get();
        $query = "SELECT * FROM " . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . " WHERE voucherSeriesId = ?";
        $queryParams[] = $seriesId;

        if (isset($params['token'])) {
            $query .= " AND token LIKE ?";
            $queryParams[] = "%" . $params['token'] . "%";
        }
        if (isset($params['usages'])) {
            $query .= " AND usages = ? ";
            $queryParams[] = $params['usages'];
        }
        if (isset($params['length'])) {
            $query .= " AND length = ? ";
            $queryParams[] = $params['length'];
        }
        if (isset($params['creation_to']) && isset($params['creation_from'])) {
            $from = $db->quote($params['creation_from']);
            $to = $db->quote($params['creation_to']);
            $query .= " AND timestamp BETWEEN STR_TO_DATE(" . $from . ",'%Y-%m-%d') AND STR_TO_DATE(" . $to . ",'%Y-%m-%d')";
        } else {
            if (isset($params['creation_from'])) {
                $param = $db->quote($params['creation_from']);
                $query .= " AND timestamp >= STR_TO_DATE(" . $param . ",'%Y-%m-%d')";
            }
            if (isset($params['creation_to'])) {
                $param = $db->quote($params['creation_to']);
                $query .= " AND timestamp <= STR_TO_DATE(" . $param . ",'%Y-%m-%d') + INTERVAL 1 DAY";
            }
        }

        if (!isset($params['sort_criteria']) || !isset($params['sort_order'])) {
            $params['sort_criteria'] = "timestamp";
            $params['sort_order'] = "DESC";
        } else {
            $params['sort_criteria'] = $db->quote($params['sort_criteria']);
            $params['sort_order'] = $db->quote($params['sort_order']);
        }

        $query .= " ORDER BY " . $params['sort_criteria'] . " " . $params['sort_order'];

        try {
            $codes = $db->fetchAll($query, array_values($queryParams));
        } catch (Exception $e) {
            return false;
        }
        return $codes;
    }


    public static function getCountByUsages($usages = 1, $seriesId = null)
    {
        $query = 'SELECT COUNT(*) as count FROM ' . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . " WHERE usages >= ? ";
        $params[] = $usages;
        if (isset($seriesId)) {
            $query .= " AND voucherSeriesId = ?";
            $params[] = $seriesId;
        }

        $db = \Pimcore\Resource::get();
        try {
            return $db->fetchOne($query, $params);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getCountBySeriesId($seriesId)
    {
        $query = 'SELECT COUNT(*) as count FROM ' . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . ' WHERE voucherSeriesId = ?';
        $params[] = $seriesId;

        $db = \Pimcore\Resource::get();
        try {
            return $db->fetchOne($query, $params);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getCountByReservation($seriesId = null)
    {
        $query = 'SELECT COUNT(t.id) FROM ' . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . " as t
            INNER JOIN " . OnlineShop_Framework_VoucherService_Reservation_Resource::TABLE_NAME . " as r ON t.token = r.token";
        if (isset($seriesId)) {
            $query .= " WHERE voucherSeriesId = ?";
            $params[] = $seriesId;
        }

        $db = \Pimcore\Resource::get();
        try {
            return $db->fetchOne($query, $params);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $length
     * @param null|string $seriesId
     * @return null|string
     */
    public static function getCountByLength($length, $seriesId = null)
    {
        $query = 'SELECT COUNT(*) as count FROM ' . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . ' WHERE length = ?';
        $params = [$length];
        if (isset($seriesId)) {
            $query .= " AND voucherSeriesId = ?";
            $params[] = $seriesId;
        }

        $db = \Pimcore\Resource::get();

        try {
            $result = $db->fetchOne($query, $params);
            return $result;
        } catch (Exception $e) {
            return null;
        }
    }


    public static function cleanUpTokens($seriesId, $filter, $maxUsages = 1)
    {
        // TODO Reservations etc
        $query = "DELETE FROM " . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . " WHERE voucherSeriesId = ? ";

        $params[] = $seriesId;
        $db = \Pimcore\Resource::get();
        try {
            if (isset($filter['used'])) {
                $queryParts[] = "usages >= " . $maxUsages;
            }
            if (isset($filter['unused'])) {
                $queryParts[] = "usages = 0";
            }

            if (isset($filter['olderThan'])) {
                $param = $db->quote($filter['olderThan']);
                $queryParts[] = "timestamp < STR_TO_DATE(" . $param . ",'%Y-%m-%d')";
            }

            if (is_array($queryParts)) {
                if (sizeof($queryParts) > 1) {
                    $query = $query . " AND (" . implode(' OR ', $queryParts) . ")";
                } else {
                    $query = $query . " AND " . $queryParts[0];
                }
                $db->query($query, $params);
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }


    /**
     * @param $codes
     * @return bool
     */
    public static function tokensExist($codes)
    {
        $db = \Pimcore\Resource::get();

        if (!is_array($codes)) {
            $token = [$codes];
        }

        $query = "SELECT EXISTS(SELECT id FROM " . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . " WHERE token IN ('" . implode("', '", $codes) . "'))";

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

}