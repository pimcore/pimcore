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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token;

// TODO - Log Errors

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property Token $model
 */
class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    public const TABLE_NAME = 'ecommerceframework_vouchertoolkit_tokens';

    public function __construct()
    {
        $this->db = \Pimcore\Db::get();
    }

    /**
     * @param string $code
     *
     * @throws NotFoundException
     */
    public function getByCode(string $code): void
    {
        $result = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME . ' WHERE token = ?', [$code]);
        if (empty($result)) {
            throw new NotFoundException('Token ' . $code . ' not found.');
        }
        $this->assignVariablesToModel($result);
        $this->model->setValue('id', $result['id']);
    }

    /**
     * @param CartInterface|null $cart
     *
     * @return bool
     */
    public function isReserved(CartInterface $cart = null): bool
    {
        $reservation = Reservation::get($this->model->getToken(), $cart);

        return $reservation !== null;
    }

    public function getTokenUsages(string $code): ?int
    {
        try {
            return (int) $this->db->fetchOne('SELECT usages FROM ' . self::TABLE_NAME . ' WHERE token = ?', [$code]);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function apply(): bool
    {
        try {
            $this->db->executeQuery('UPDATE ' . self::TABLE_NAME . ' SET usages=usages+1 WHERE token = ?', [$this->model->getToken()]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function unuse(): bool
    {
        try {
            $this->db->executeQuery('UPDATE ' . self::TABLE_NAME . ' SET usages=usages-1 WHERE token = ?', [$this->model->getToken()]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function check(CartInterface $cart): void
    {
    }
}
