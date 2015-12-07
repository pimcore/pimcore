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


namespace OnlineShop\Framework\OrderManager\Order\Listing\Filter\Search;

use OnlineShop\Framework\OrderManager\Order\Listing\Filter\AbstractSearch;
use OnlineShop\Framework\OrderManager\IOrderList;

class PaymentReference extends AbstractSearch
{
    /**
     * @return string
     */
    protected function getConditionColumn()
    {
        return 'paymentInfo.paymentReference';
    }

    /**
     * @return string
     */
    protected function getConditionValue()
    {
        $value = parent::getConditionValue();
        $value = ',' . $value . ',';

        return $value;
    }

    /**
     * Join paymentInfo
     *
     * @param IOrderList $orderList
     */
    protected function prepareApply(IOrderList $orderList)
    {
        $orderList->joinPaymentInfo();
    }
}