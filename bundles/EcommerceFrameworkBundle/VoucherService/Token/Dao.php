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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token;

/**
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
     * @return bool
     */
    public function getByCode($code)
    {
        try {
            $result = $this->db->fetchRow('SELECT * FROM ' . self::TABLE_NAME . ' WHERE token = ?', $code);
            if (empty($result)) {
                throw new \Exception('Token ' . $code . ' not found.');
            }
            $this->assignVariablesToModel($result);
            $this->model->setValue('id', $result['id']);

            return true;
        } catch (\Exception $e) {
            //            \Pimcore\Log\Simple::log('VoucherService',$e);
            return false;
        }
    }

    /**
     * @return bool
     *
     * @param CartInterface $cart
     */
    public function isReserved(CartInterface $cart = null)
    {
        $reservation = Reservation::get($this->model->getToken(), $cart);
        if (!$reservation->exists()) {
            return false;
        }

        return true;
    }

    public function getTokenUsages($code)
    {
        try {
            return $this->db->fetchOne('SELECT usages FROM ' . self::TABLE_NAME . ' WHERE token = ?', $code);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function apply()
    {
        try {
            $this->db->query('UPDATE ' . self::TABLE_NAME . ' SET usages=usages+1 WHERE token = ?', [$this->model->getToken()]);

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
            $this->db->query('UPDATE ' . self::TABLE_NAME . ' SET usages=usages-1 WHERE token = ?', [$this->model->getToken()]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function check($cart)
    {
    }
}
