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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\Statistic;


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