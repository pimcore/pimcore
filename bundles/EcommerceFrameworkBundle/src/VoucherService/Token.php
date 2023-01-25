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
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao;
use Pimcore\Db;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method Dao getDao()
 * @method bool isReserved()
 */
class Token extends AbstractModel
{
    public int $id;

    public int $voucherSeriesId;

    public string $token;

    public int $length;

    public string $type;

    public int $usages;

    public string $timestamp;

    public static function getByCode(string $code): ?Token
    {
        try {
            $config = new self();
            $config->getDao()->getByCode($code);

            return $config;
        } catch (NotFoundException $ex) {
            return null;
        }
    }

    public function isUsed(int $maxUsages = 1): bool
    {
        if ($this->usages >= $maxUsages) {
            return true;
        }

        return false;
    }

    public static function isUsedToken(string $code, int $maxUsages = 1): bool
    {
        $db = Db::get();
        $query = 'SELECT usages FROM ' . Dao::TABLE_NAME . ' WHERE token = ? ';
        $params[] = $code;

        try {
            $tokenUsed = $db->fetchOne($query, $params);

            return $tokenUsed >= $maxUsages;
            // If an Error occurs the token is defined as used.
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * @param int|null $maxUsages
     * @param bool $isCheckout In the checkout there is one reservation more, the one of the current order.
     *
     * @return bool
     */
    public function check(int $maxUsages = null, bool $isCheckout = false): bool
    {
        if (isset($maxUsages)) {
            if ($this->getUsages() + Reservation::getReservationCount($this->getToken()) - (int)$isCheckout <= $maxUsages) {
                return true;
            }

            return false;
        } else {
            return !$this->isUsed() && !$this->isReserved();
        }
    }

    public static function tokenExists(string $code): bool
    {
        $db = Db::get();
        $query = 'SELECT EXISTS(SELECT id FROM ' . Dao::TABLE_NAME . ' WHERE token = ?)';
        $result = $db->fetchOne($query, [$code]);

        if ($result == 0) {
            return false;
        }

        return true;
    }

    public function release(?CartInterface $cart): bool
    {
        return Reservation::releaseToken($this->getToken(), $cart);
    }

    public function apply(): bool
    {
        if ($this->getDao()->apply()) {
            Statistic::increaseUsageStatistic($this->getVoucherSeriesId());

            return true;
        }

        return false;
    }

    public function unuse(): bool
    {
        if ($this->getDao()->unuse()) {
            return true;
        }

        return false;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function setTimestamp(string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getVoucherSeriesId(): int
    {
        return $this->voucherSeriesId;
    }

    public function setVoucherSeriesId(int $voucherSeriesId): void
    {
        $this->voucherSeriesId = $voucherSeriesId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getUsages(): int
    {
        return $this->usages;
    }

    public function setUsages(int $usages): void
    {
        $this->usages = $usages;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
