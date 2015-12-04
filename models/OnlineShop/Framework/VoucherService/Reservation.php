<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_Framework_VoucherService_Reservation extends \Pimcore\Model\AbstractModel
{

    public $id;
    public $token;
    public $timestamp;
    public $cart_id;

    /**
     * @param $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool|OnlineShop_Framework_VoucherService_Reservation
     */
    public static function get($code, \OnlineShop\Framework\CartManager\ICart $cart = null)
    {
        try {
            $config = new self();
            $config->getResource()->get($code, $cart);
            return $config;
        } catch (Exception $ex) {
//            Logger::debug($ex->getMessage());
            return false;
        }
    }

    public function exists()
    {
        return isset($this->id);
    }

    /**
     * Check whether the reservation object contains a reservations.
     * @return bool
     */
    public function check($cart_id)
    {
        return $cart_id == $this->getCartId();
    }

    public static function create($code, $cart_id)
    {
        try {
            $config = new self();
            $config->getResource()->create($code, $cart_id);
            return $config;
        } catch (Exception $ex) {
//            Logger::debug($ex->getMessage());
            return false;
        }
    }

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool
     */
    public static function releaseToken($code, \OnlineShop\Framework\CartManager\ICart $cart = null)
    {
        $db = \Pimcore\Resource::get();

        $query = "DELETE FROM " . OnlineShop_Framework_VoucherService_Reservation_Resource::TABLE_NAME . " WHERE token = ?";
        $params[] = $code;

        if (isset($cart)) {
            $query .= " AND cart_id = ?";
            $params[] = $cart->getId();
        }

        try {
            $db->query($query, $params);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function remove()
    {
        return $this->getResource()->remove();
    }

    /**
     * @param int $duration in Minutes
     * @param string|null $seriesId
     *
     * @return bool
     */
    public static function cleanUpReservations($duration, $seriesId = null)
    {
        $query = "DELETE FROM " . OnlineShop_Framework_VoucherService_Reservation_Resource::TABLE_NAME . " WHERE MINUTE(TIMEDIFF(timestamp, NOW())) >= ?";
        $params[] = $duration;

        if (isset($seriesId)) {
            $query .= " AND token in (SELECT token FROM " . OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME . " WHERE voucherSeriesId = ?)";
            $params[] = $seriesId;
        }

        $db = \Pimcore\Resource::get();
        try {
            $db->query($query, $params);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function reservationExists($code, $cart)
    {
        $db = \Pimcore\Resource::get();
        $query = "SELECT EXISTS(SELECT id FROM " . OnlineShop_Framework_VoucherService_Reservation_Resource::TABLE_NAME . " WHERE token = ? and cart_id = ?)";

        try {
            return (bool)$db->fetchOne($query, [$code, $cart->getId()]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $code
     * @return bool|int
     */
    public static function getReservationCount($code)
    {

        $db = \Pimcore\Resource::get();
        $query = "SELECT COUNT(*) FROM " . OnlineShop_Framework_VoucherService_Reservation_Resource::TABLE_NAME . " WHERE token = ? ";

        try {
            $count = $db->fetchOne($query, $code);
            return (int)$count;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getCartId()
    {
        return $this->cart_id;
    }

    /**
     * @param mixed $cart_id
     */
    public function setCartId($cart_id)
    {
        $this->cart_id = $cart_id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param mixed $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }
}