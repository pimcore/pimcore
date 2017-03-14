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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model;

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