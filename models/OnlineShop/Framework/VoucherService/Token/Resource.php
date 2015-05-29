<?php

// TODO - Log Errors

class OnlineShop_Framework_VoucherService_Token_Resource extends \Pimcore\Model\Resource\AbstractResource
{
    const TABLE_NAME = "plugins_onlineshop_vouchertoolkit_tokens";

    protected $db;

    public function __construct()
    {
        $this->db = \Pimcore\Resource::get();
    }

    /**
     * @param $code
     * @return bool
     */
    public function getByCode($code)
    {
        try {
            $result = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME . " WHERE token = ?", $code);
            if (empty($result)) {
                throw new Exception("Token " . $code . " not found.");
            }
            $this->assignVariablesToModel($result);
            $this->model->setValue('id', $result['id']);

        } catch (Exception $e) {
//            \Pimcore\Log\Simple::log('VoucherService',$e);
            return false;
        }
    }

    /**
     * @return bool
     *
     * @param OnlineShop_Framework_ICart $cart
     */
    public function isReserved($cart = null)
    {
        $reservation = OnlineShop_Framework_VoucherService_Reservation::get($this->model->getToken(), $cart);
        if (!$reservation->exists()) {
            return false;
        }
        return true;
    }

    public function getTokenUsages($code)
    {
        try {
            return $this->db->fetchOne("SELECT usages FROM " . self::TABLE_NAME . " WHERE token = ?", $code);
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * @param string $token
     * @param int $usages
     * @return bool
     */
    public static function isUsedToken($token, $usages = 1)
    {
        $db = \Pimcore\Resource::get();

        $query = "SELECT usages, seriesId FROM " . self::TABLE_NAME . " WHERE token = ? ";
        $params[] = $token;


        try {
            $usages['usages'] = $db->fetchOne($query, $params);
            if ($usages > $usages) {
                return $usages['seriesId'];
            } else {
                return false;
            }
            // If an Error occurs the token is defined as used.
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * @return bool
     */
    public function apply()
    {
        try {
            $this->db->query("UPDATE " . self::TABLE_NAME . " SET usages=usages+1 WHERE token = ?", [$this->model->getToken()]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function check($cart){

    }

}