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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService;

class Statistic extends \Pimcore\Model\AbstractModel
{

    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $tokenSeriesId;
    /**
     * @var int
     */
    public $date;

    /**
     * @param int $id
     * @return bool|Statistic
     */
    public function getById($id)
    {
        try {
            $config = new self();
            $config->getDao()->getById($id);
            return $config;
        } catch (\Exception $ex) {
//            Logger::debug($ex->getMessageN());
            return false;
        }
    }

    /**
     * @param $seriesId
     * @throws \Exception
     *
     * @return bool
     */
    public static function getBySeriesId($seriesId, $usagePeriod = null)
    {
        $db = \Pimcore\Db::get();

        $query = "SELECT date, COUNT(*) as count FROM " . \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Statistic\Dao::TABLE_NAME . " WHERE voucherSeriesId = ?";
        $params[] = $seriesId;
        if ($usagePeriod) {
            $query .= " AND (TO_DAYS(NOW()) - TO_DAYS(date)) < ?";
            $params[] = $usagePeriod;
        }

        $query .= " GROUP BY date";

        try {
            $result = $db->fetchPairs($query, $params);
            return $result;
        } catch (\Exception $e) {
//            \Pimcore\Log\Simple::log('VoucherService',$e);
            return false;
        }
    }

    /**
     * @param $seriesId
     * @return bool
     */
    public static function increaseUsageStatistic($seriesId)
    {
        $db = $db = \Pimcore\Db::get();
        try {
            $db->query("INSERT INTO " . \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Statistic\Dao::TABLE_NAME . " (voucherSeriesId,date) VALUES (?,NOW())", $seriesId);

        } catch (\Exception $e) {
//            \Pimcore\Log\Simple::log('VoucherService',$e);
            return false;
        }
    }


    /**
     * @param int $duration days
     * @param string|null $seriesId
     * @return bool
     */
    public static function cleanUpStatistics($duration, $seriesId = null){
        $query = "DELETE FROM " . \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Statistic\Dao::TABLE_NAME . " WHERE DAY(DATEDIFF(date, NOW())) >= ?";
        $params[] = $duration;

        if (isset($seriesId)) {
            $query .= " AND voucherSeriesId = ?";
            $params[] = $seriesId;
        }

        $db = \Pimcore\Db::get();
        try {
            $db->query($query, $params);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return int
     */
    public
    function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public
    function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public
    function getTokenSeriesId()
    {
        return $this->tokenSeriesId;
    }

    /**
     * @param string $tokenSeriesId
     */
    public
    function setTokenSeriesId($tokenSeriesId)
    {
        $this->tokenSeriesId = $tokenSeriesId;
    }

    /**
     * @return int
     */
    public
    function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     */
    public
    function setDate($date)
    {
        $this->date = $date;
    }

}