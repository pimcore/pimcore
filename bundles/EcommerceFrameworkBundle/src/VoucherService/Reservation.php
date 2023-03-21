<?php
declare(strict_types=1);

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
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method Dao getDao()
 */
class Reservation extends AbstractModel
{
    public ?int $id = null;

    public ?string $token = null;

    public ?string $timestamp = null;

    public ?string $cart_id = null;

    public static function get(string $code, CartInterface $cart = null): ?self
    {
        try {
            $config = new self();
            $config->getDao()->get($code, $cart);

            return $config;
        } catch (NotFoundException $ex) {
            //            Logger::debug($ex->getMessage());
            return null;
        }
    }

    public static function create(string $code, CartInterface $cart): ?self
    {
        try {
            $config = new self();
            $config->getDao()->create($code, $cart);

            return $config;
        } catch (\Exception $ex) {
            //            Logger::debug($ex->getMessage());
            return null;
        }
    }

    public static function releaseToken(string $code, CartInterface $cart = null): bool
    {
        $db = \Pimcore\Db::get();

        $query = 'DELETE FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao::TABLE_NAME . ' WHERE token = ?';
        $params[] = $code;

        if (isset($cart)) {
            $query .= ' AND cart_id = ?';
            $params[] = $cart->getId();
        }

        try {
            $db->executeQuery($query, $params);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function remove(): bool
    {
        return $this->getDao()->remove();
    }

    /**
     * @param int $duration in Minutes
     */
    public static function cleanUpReservations(int $duration, int $seriesId = null): bool
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

    public static function reservationExists(string $code, CartInterface $cart): bool
    {
        $db = \Pimcore\Db::get();
        $query = 'SELECT EXISTS(SELECT id FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao::TABLE_NAME . ' WHERE token = ? and cart_id = ?)';

        try {
            return (bool)$db->fetchOne($query, [$code, $cart->getId()]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getReservationCount(string $code): bool|int
    {
        $db = \Pimcore\Db::get();
        $query = 'SELECT COUNT(*) FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation\Dao::TABLE_NAME . ' WHERE token = ? ';

        try {
            $count = $db->fetchOne($query, [$code]);

            return (int)$count;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    public function getCartId(): ?string
    {
        return $this->cart_id;
    }

    public function setCartId(?string $cart_id): void
    {
        $this->cart_id = $cart_id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }

    public function setTimestamp(?string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
