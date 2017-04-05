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

// TODO - Log Errors

class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    const TABLE_NAME = "ecommerceframework_vouchertoolkit_tokens";

    public function __construct()
    {
        $this->db = \Pimcore\Db::get();
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
                throw new \Exception("Token " . $code . " not found.");
            }
            $this->assignVariablesToModel($result);
            $this->model->setValue('id', $result['id']);
        } catch (\Exception $e) {
            //            \Pimcore\Log\Simple::log('VoucherService',$e);
            return false;
        }
    }

    /**
     * @return bool
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     */
    public function isReserved(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart = null)
    {
        $reservation = \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation::get($this->model->getToken(), $cart);
        if (!$reservation->exists()) {
            return false;
        }

        return true;
    }

    public function getTokenUsages($code)
    {
        try {
            return $this->db->fetchOne("SELECT usages FROM " . self::TABLE_NAME . " WHERE token = ?", $code);
        } catch (\Exception $e) {
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
        $db = \Pimcore\Db::get();

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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function unuse()
    {
        try {
            $this->db->query("UPDATE " . self::TABLE_NAME . " SET usages=usages-1 WHERE token = ?", [$this->model->getToken()]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function check($cart)
    {
    }
}
