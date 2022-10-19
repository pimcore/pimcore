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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager;

/**
 * Interface StatusInterface
 */
interface StatusInterface
{
    const STATUS_PENDING = 'paymentPending';

    const STATUS_AUTHORIZED = 'paymentAuthorized';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_CLEARED = 'committed';

    /**
     * payment reference from payment provider
     *
     * @return string
     */
    public function getPaymentReference(): string;

    /**
     * pimcore internal payment id, necessary to identify payment information in order object
     *
     * @return string
     */
    public function getInternalPaymentId(): string;

    /**
     * payment message provided from payment provider - e.g. error message on error
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * internal pimcore order status - see also constants \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder::ORDER_STATE_*
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * additional payment data
     *
     * @return array
     */
    public function getData(): array;
}
