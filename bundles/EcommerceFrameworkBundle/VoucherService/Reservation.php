<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao;
use Pimcore\Model\AbstractModel;

/**
 * @method Dao getDao()
 */
class Reservation extends AbstractModel
{
    /** @var int|null */
    public $id;

    /** @var string|null */
    public $token;

    /** @var int|null */
    public $timestamp;

    /** @var string|null */
    public $cart_id;

    /**
     * @param string $code
     * @param CartInterface|null $cart
     *
     * @return self|null
     */
    public static function get($code, CartInterface $cart = null): ?self
    {
        try {
            $config = new self();
            $config->getDao()->get($code, $cart);

            return $config;
        } catch (\Exception $ex) {
            //            Logger::debug($ex->getMessage());
            return null;
        }
    }

    public function exists()
    {
        return isset($this->id);
    }

    /**
     * Check whether the reservation object contains a reservations.
     *
     * @param int $cart_id
     *
     * @return bool
     */
    public function check($cart_id)
    {
        return $cart_id == $this->getCartId();
    }

    public static function create($code, $cart_id): ?self
    {
        try {
            $config = new self();
            $config->getDao()->create($code, $cart_id);

            return $config;
        } catch (\Exception $ex) {
            //            Logger::debug($ex->getMessage());
            return null;
        }
    }

    /**
     * @param string $code
     * @param CartInterface|null $cart
     *
     * @return bool
     */
    public static function releaseToken($code, CartInterface $cart = null): bool
    {
        $db = \Pimcore\Db::get();

        $query = 'DELETE FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao::TABLE_NAME . ' WHERE token = ?';
        $params[] = $code;

        if (isset($cart)) {
            $query .= ' AND cart_id = ?';
            $params[] = $cart->getId();
        }

        try {
            $db->query($query, $params);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function remove(): bool
    {
        return $this->getDao()->remove();
    }

    /**
     * @param int $duration in Minutes
     * @param string|null $seriesId
     *
     * @return bool
     */
    public static function cleanUpReservations($duration, $seriesId = null): bool
    {
        $query = 'DELETE FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao::TABLE_NAME . ' WHERE TIMESTAMPDIFF(MINUTE, timestamp , NOW())  >= ?';
        $params[] = $duration;

        if (isset($seriesId)) {
            $query .= ' AND token in (SELECT token FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . ' WHERE voucherSeriesId = ?)';
            $params[] = $seriesId;
        }

        $db = \Pimcore\Db::get();

        try {
            $db->executeQuery($query, $params);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function reservationExists($code, $cart): bool
    {
        $db = \Pimcore\Db::get();
        $query = 'SELECT EXISTS(SELECT id FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao::TABLE_NAME . ' WHERE token = ? and cart_id = ?)';

        try {
            return (bool)$db->fetchOne($query, [$code, $cart->getId()]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $code
     *
     * @return bool|int
     */
    public static function getReservationCount($code)
    {
        $db = \Pimcore\Db::get();
        $query = 'SELECT COUNT(*) FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao::TABLE_NAME . ' WHERE token = ? ';

        try {
            $count = $db->fetchOne($query, $code);

            return (int)$count;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return string|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string|null $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @return string|null
     */
    public function getCartId()
    {
        return $this->cart_id;
    }

    /**
     * @param string|null $cart_id
     */
    public function setCartId($cart_id)
    {
        $this->cart_id = $cart_id;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int|null $timestamp
     */
    public function setTimestamp($timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
