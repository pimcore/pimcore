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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Model\DataObject\Listing\Concrete;

interface RecurringPaymentInterface
{
    /**
     * Payment supports recurring payment
     *
     * @return bool
     */
    public function isRecurringPaymentEnabled();

    /**
     * @param AbstractOrder $sourceOrder
     * @param object $paymentBrick
     *
     * @return mixed
     */
    public function setRecurringPaymentSourceOrderData(AbstractOrder $sourceOrder, $paymentBrick);

    /**
     * @param Concrete $orderListing
     *
     * @return Concrete
     */
    public function applyRecurringPaymentCondition(Concrete $orderListing, $additionalParameters = []);
}
