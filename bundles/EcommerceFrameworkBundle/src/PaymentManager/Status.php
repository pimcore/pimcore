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

class Status implements StatusInterface
{
    /**
     * internal pimcore order status - see also constants \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder::ORDER_STATE_*
     *
     * @var string
     */
    protected string $status;

    /**
     * pimcore internal payment id, necessary to identify payment information in order object
     *
     * @var string
     */
    protected string $internalPaymentId;

    /**
     * payment reference from payment provider
     *
     * @var string
     */
    protected string $paymentReference;

    /**
     * payment message provided from payment provider - e.g. error message on error
     *
     * @var string
     */
    protected string $message;

    /**
     * additional payment data
     *
     * @var array
     */
    protected array $data = [];

    /**
     * @param string $internalPaymentId
     * @param string $paymentReference
     * @param string $message
     * @param string $status
     * @param array  $data  extended data
     */
    public function __construct(string $internalPaymentId, string $paymentReference, string $message, string $status, array $data = [])
    {
        $this->internalPaymentId = $internalPaymentId;
        $this->paymentReference = $paymentReference;
        $this->message = $message;
        $this->status = $status;
        $this->data = $data;
    }

    public function getInternalPaymentId(): string
    {
        return $this->internalPaymentId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getPaymentReference(): string
    {
        return $this->paymentReference;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
