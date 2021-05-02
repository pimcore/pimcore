<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Carbon\Carbon;

/**
 * Abstract base class for payment information field collection
 */
abstract class AbstractPaymentInformation extends \Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData
{
    /**
     * @return Carbon|null
     */
    abstract public function getPaymentStart(): ?Carbon;

    /**
     * @param Carbon|null $paymentStart
     *
     * @return mixed
     */
    abstract public function setPaymentStart(?Carbon $paymentStart);

    /**
     * @return Carbon|null
     */
    abstract public function getPaymentFinish(): ?Carbon;

    /**
     * @param Carbon|null $paymentFinish
     *
     * @return mixed
     */
    abstract public function setPaymentFinish(?Carbon $paymentFinish);

    /**
     * @return string|null
     */
    abstract public function getPaymentReference(): ?string;

    /**
     * @param string|null $paymentReference
     *
     * @return mixed
     */
    abstract public function setPaymentReference(?string $paymentReference);

    /**
     * @return string|null
     */
    abstract public function getPaymentState(): ?string;

    /**
     * @param string|null $paymentState
     *
     * @return mixed
     */
    abstract public function setPaymentState(?string $paymentState);

    /**
     * @return string|null
     */
    abstract public function getMessage(): ?string;

    /**
     * @return string|null
     */
    abstract public function getProviderData(): ?string;

    /**
     * @param string|null $message
     *
     * @return mixed
     */
    abstract public function setMessage(?string $message);

    /**
     * @return string|null
     */
    abstract public function getInternalPaymentId(): ?string;

    /**
     * @param string|null $internalPaymentId
     *
     * @return mixed
     */
    abstract public function setInternalPaymentId(?string $internalPaymentId);
}
