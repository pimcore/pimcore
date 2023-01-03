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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Model\DataObject\Listing\Concrete;

interface RecurringPaymentInterface extends PaymentInterface
{
    /**
     * Payment supports recurring payment
     */
    public function isRecurringPaymentEnabled(): bool;

    public function setRecurringPaymentSourceOrderData(AbstractOrder $sourceOrder, object $paymentBrick): void;

    public function applyRecurringPaymentCondition(Concrete $orderListing, array $additionalParameters = []): void;
}
