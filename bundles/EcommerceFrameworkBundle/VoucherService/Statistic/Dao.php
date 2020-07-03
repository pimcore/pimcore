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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Statistic;

class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    const TABLE_NAME = 'ecommerceframework_vouchertoolkit_statistics';

    public function __construct()
    {
        $this->db = \Pimcore\Db::get();
    }

    /**
     * @param int $id
     *
     * @return bool|string
     */
    public function getById($id)
    {
        try {
            $result = $this->db->fetchOne('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = ? GROUP BY date', $id);
            if (empty($result)) {
                throw new \Exception('Statistic with id ' . $id . ' not found.');
            }
            $this->assignVariablesToModel($result);

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
}
