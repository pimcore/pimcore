<?

class OnlineShop_Framework_VoucherService_Statistic_Resource extends \Pimcore\Model\Resource\AbstractResource
{
    const TABLE_NAME = "plugins_onlineshop_vouchertoolkit_statistics";

    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Pimcore\Resource::get();
    }

    /**
     * @param int $id
     * @return bool|string
     */
    public function getById($id){
        try {
            $result = $this->db->fetchOne("SELECT * FROM " . self::TABLE_NAME . " WHERE id = ? GROUP BY date", $id);
            if (empty($result)) {
                throw new Exception("Statistic with id " . $id . " not found.");
            }
            $this->assignVariablesToModel($result);
            return $result;
        } catch (Exception $e) {
//            \Pimcore\Log\Simple::log('VoucherService',$e);
            return false;
        }
    }

}