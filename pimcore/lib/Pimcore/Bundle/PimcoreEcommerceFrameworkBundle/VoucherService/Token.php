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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService;

class Token extends \Pimcore\Model\AbstractModel
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $voucherSeriesId;
    /**
     * @var string
     */
    public $token;
    /**
     * @var int
     */
    public $length;
    /**
     * @var string
     */
    public $type;
    /**
     * @var int
     */
    public $usages;
    /**
     * @var int
     */
    public $timestamp;


    /**
     * @param string $code
     * @return bool|Token
     */
    public static function getByCode($code)
    {
        try {
            $config = new self();
            $config->getDao()->getByCode($code);

            return $config;
        } catch (\Exception $ex) {
            //            Logger::debug($ex->getMessage());
            return false;
        }
    }

    /**
     * @param int $maxUsages
     * @return bool
     */
    public function isUsed($maxUsages = 1)
    {
        if ($this->usages >= $maxUsages) {
            return true;
        }

        return false;
    }

    public static function isUsedToken($code, $maxUsages = 1)
    {
        try {
            $usages = self::getDao()->getTokenUsages($code);

            return $usages <= $maxUsages;
        } catch (\Exception $ex) {
            //            Logger::debug($ex->getMessage());
            return true;
        }
    }

    /**
     * @param null|int $maxUsages
     * @param bool $isCheckout In the checkout there is one reservation more, the one of the current order.
     * @return bool
     */
    public function check($maxUsages = null, $isCheckout = false)
    {
        if (isset($maxUsages)) {
            if ($this->getUsages() + \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Reservation::getReservationCount($this->getToken()) - (int)$isCheckout <= $maxUsages) {
                return true;
            }

            return false;
        } else {
            return !$this->isUsed() && !$this->isReserved();
        }
    }

    /**
     * @return mixed
     */
    public function isReserved()
    {
        return $this->getDao()->isReserved();
    }


    /**
     * @param $code
     * @return bool
     */
    public static function tokenExists($code)
    {
        $db = \Pimcore\Db::get();

        $query = "SELECT EXISTS(SELECT id FROM " . self::TABLE_NAME . " WHERE token = ?)";

        $result = $db->fetchOne($query, $code);

        if ($result == 0) {
            return false;
        }

        return true;
    }

    public function release($cart)
    {
        return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Reservation::releaseToken($this, $cart);
    }

    public function apply()
    {
        if ($this->getDao()->apply()) {
            \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Statistic::increaseUsageStatistic($this->getVoucherSeriesId());

            return true;
        }

        return false;
    }

    public function unuse()
    {
        if ($this->getDao()->unuse()) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return int
     */
    public function getVoucherSeriesId()
    {
        return $this->voucherSeriesId;
    }

    /**
     * @param int $voucherSeriesId
     */
    public function setVoucherSeriesId($voucherSeriesId)
    {
        $this->voucherSeriesId = $voucherSeriesId;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getUsages()
    {
        return $this->usages;
    }

    /**
     * @param int $usages
     */
    public function setUsages($usages)
    {
        $this->usages = $usages;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}
