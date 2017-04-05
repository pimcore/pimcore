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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;

abstract class AbstractVoucherSeries extends \Pimcore\Model\Object\Concrete
{

    /**
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    abstract public function getTokenSettings();


    /**
     * @return bool|\Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ITokenManager
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getTokenManager()
    {
        $items = $this->getTokenSettings();
        if ($items && $items->get(0)) {

            // name of fieldcollection class
            $configuration = $items->get(0);

            return Factory::getInstance()->getTokenManager($configuration);
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function getExistingLengths()
    {
        $db = \Pimcore\Db::get();

        $query = "
            SELECT length, COUNT(*) AS count FROM " . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . "
            WHERE voucherSeriesId = ?
            GROUP BY length";

        try {
            $lengths = $db->fetchAll($query, [$this->getId()]);

            $result = [];
            foreach ($lengths as $lengthEntry) {
                $result[$lengthEntry['length']] = $lengthEntry['count'];
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
}
