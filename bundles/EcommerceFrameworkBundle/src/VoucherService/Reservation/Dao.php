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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation;

// TODO - Log Exceptions

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property Reservation $model
 */
class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    const TABLE_NAME = 'ecommerceframework_vouchertoolkit_reservations';

    public function __construct()
    {
        $this->db = \Pimcore\Db::get();
    }

    /**
     * @param string $code
     * @param CartInterface|null $cart
     *
     * @throws NotFoundException
     */
    public function get(string $code, CartInterface $cart = null): void
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE token = ?';
        $params[] = $code;
        if (isset($cart)) {
            $query .= ' AND cart_id = ?';
            $params[] = $cart->getId();
        }

        $result = $this->db->fetchAssociative($query, $params);
        if (empty($result)) {
            throw new NotFoundException('Reservation for token ' . $code . ' not found.');
        }
        $this->assignVariablesToModel($result);
        $this->model->setValue('id', $result['id']);
        $this->model->setCartId($result['cart_id']);
    }

    public function create(string $code, CartInterface $cart): void
    {
        if (!Reservation::reservationExists($code, $cart)) {
            // Single Type Token --> only one token per Cart! --> Update on duplicate key!
            $this->db->executeQuery('INSERT INTO ' . self::TABLE_NAME . ' (token,cart_id,timestamp) VALUES (?,?,NOW())', [$code, $cart->getId()]);
        }

        $this->get($code, $cart);
    }

    public function remove(): bool
    {
        $this->db->delete(self::TABLE_NAME, ['token' => $this->model->getToken()]);

        return true;
    }

    /**
     * @param int|null $seriesId
     *
     * @return bool|int
     */
    public static function getReservedTokenCount(int $seriesId = null): bool|int
    {
        $db = \Pimcore\Db::get();

        $query = 'SELECT COUNT(*) FROM ' . self::TABLE_NAME;
        $params = [];

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

    public static function isReservedToken(string $token): bool
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
