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

namespace OnlineShop\Framework\Model;

abstract class AbstractVoucherSeries extends \Pimcore\Model\Object\Concrete
{

    /**
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public abstract function getTokenSettings();


    /**
     * @return bool|\OnlineShop\Framework\VoucherService\TokenManager\ITokenManager
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getTokenManager()
    {

        $items = $this->getTokenSettings();
        if ($items && $items->get(0)) {

            // name of fieldcollection class
            $configuration = $items->get(0);
            return \OnlineShop\Framework\Factory::getInstance()->getTokenManager($configuration);

        }
        return false;
    }

    /**
     * @return bool|string
     */
    public function getExistingLengths(){
        $db = \Pimcore\Db::get();

        $query = "
            SELECT length FROM " . \OnlineShop\Framework\VoucherService\Token\Dao::TABLE_NAME . "
            WHERE voucherSeriesId = ?
            GROUP BY length";

        try {
            return $db->fetchAssoc($query, $this->getId());
        }catch (\Exception $e){
            return false;
        }
    }

}