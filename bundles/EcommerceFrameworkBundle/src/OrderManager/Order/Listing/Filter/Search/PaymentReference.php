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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\Search;

use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter\AbstractSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;

class PaymentReference extends AbstractSearch
{
    protected function getConditionColumn(): string
    {
        return 'paymentInfo.paymentReference';
    }

    protected function getConditionValue(): string
    {
        $value = parent::getConditionValue();
        $value = ',' . $value . ',';

        return $value;
    }

    /**
     * Join paymentInfo
     *
     * @param OrderListInterface $orderList
     */
    protected function prepareApply(OrderListInterface $orderList): void
    {
        $orderList->joinPaymentInfo();
    }
}
