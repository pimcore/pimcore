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


namespace OnlineShop\Framework\VoucherService\Statistic;


class Dao extends \Pimcore\Model\Dao\AbstractDao
{
    const TABLE_NAME = "plugins_onlineshop_vouchertoolkit_statistics";

    public function __construct()
    {
        parent::__construct();
        $this->db = \Pimcore\Resource::get();
    }

    /**
     * @param int $id
     * @return bool|string
     */
    public function getById($id){
        try {
            $result = $this->db->fetchOne("SELECT * FROM " . self::TABLE_NAME . " WHERE id = ? GROUP BY date", $id);
            if (empty($result)) {
                throw new \Exception("Statistic with id " . $id . " not found.");
            }
            $this->assignVariablesToModel($result);
            return $result;
        } catch (\Exception $e) {
//            \Pimcore\Log\Simple::log('VoucherService',$e);
            return false;
        }
    }

}