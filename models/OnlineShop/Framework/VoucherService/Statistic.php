<?php

class OnlineShop_Framework_VoucherService_Statistic extends \Pimcore\Model\AbstractModel
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
     * @return bool|OnlineShop_Framework_VoucherService_Statistic
     */
    public function getById($id){
        try {
            $config = new self();
            $config->getResource()->getById($id);
            return $config;
        } catch (Exception $ex) {
//            Logger::debug($ex->getMessageN());
            return false;
        }
    }

    /**
     * @param $seriesId
     * @throws Exception
     *
     * @return bool
     */
    public static function getBySeriesId($seriesId)
    {
        $db = \Pimcore\Resource::get();
        try {
            $result = $db->fetchPairs("SELECT date, COUNT(*) as count FROM " . OnlineShop_Framework_VoucherService_Statistic_Resource::TABLE_NAME . " WHERE voucherSeriesId = ? GROUP BY date", $seriesId);
            return $result;
        } catch (Exception $e) {
//            \Pimcore\Log\Simple::log('VoucherService',$e);
            return false;
        }
    }

    public static function increaseUsageStatistic($seriesId)
    {
        $db = $db = \Pimcore\Resource::get();
        try {
            $db->query("INSERT INTO " . OnlineShop_Framework_VoucherService_Statistic_Resource::TABLE_NAME . " (voucherSeriesId,date) VALUES (?,NOW())", $seriesId);

        } catch (Exception $e) {
//            \Pimcore\Log\Simple::log('VoucherService',$e);
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