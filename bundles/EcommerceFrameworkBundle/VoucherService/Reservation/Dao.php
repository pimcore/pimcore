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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation;

// TODO - Log Exceptions

class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    const TABLE_NAME = 'ecommerceframework_vouchertoolkit_reservations';

    public function __construct()
    {
        $this->db = \Pimcore\Db::get();
    }

    /**
     * @param string $code
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     *
     * @return bool|string
     */
    public function get($code, \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart = null)
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE token = ?';
        $params[] = $code;
        if (isset($cart)) {
            $query .= ' AND cart_id = ?';
            $params[] = $cart->getId();
        }

        try {
            $result = $this->db->fetchRow($query, $params);
            if (empty($result)) {
                //                throw new Exception("Reservation for token " . $code . " not found.");
                return false;
            }
            $this->assignVariablesToModel($result);
            $this->model->setValue('id', $result['id']);
            $this->model->setCartId($result['cart_id']);

            return true;
        } catch (\Exception $e) {
            var_dump($e);

            return false;
        }
    }

    public function create($code, $cart)
    {
        if (\Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation::reservationExists($code, $cart)) {
            return true;
        }
        try {
            // Single Type Token --> only one token per Cart! --> Update on duplicate key!
            $this->db->query('INSERT INTO ' . self::TABLE_NAME . ' (token,cart_id,timestamp) VALUES (?,?,NOW())', [$code, $cart->getId()]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function remove()
    {
        try {
            $this->db->deleteWhere(self::TABLE_NAME, ['token' => $this->model->getToken()]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param null|int $seriesId
     *
     * @return bool|int
     */
    public static function getReservedTokenCount($seriesId = null)
    {
        $db = \Pimcore\Db::get();

        $query = 'SELECT COUNT(*) FROM ' . self::TABLE_NAME;

        if (isset($seriesId)) {
            $query .= ' WHERE seriesId = ?';
            $params[] = $seriesId;
        }

        try {
            $count = $db->fetchOne($query, $params);
            if ($count === 0) {
                return false;
            }

            return $count;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public static function isReservedToken($token)
    {
        $db = \Pimcore\Db::get();

        $query = 'SELECT isReserved FROM ' . self::TABLE_NAME . ' WHERE token = ? ';
        $params[] = $token;

        try {
            if ($db->fetchOne($query, $params) === 0) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return true;
        }
    }
}
