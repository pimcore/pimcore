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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
     * @return self
     */
    abstract public function setPaymentStart(?Carbon $paymentStart);

    /**
     * @return Carbon|null
     */
    abstract public function getPaymentFinish(): ?Carbon;

    /**
     * @param Carbon|null $paymentFinish
     *
     * @return self
     */
    abstract public function setPaymentFinish(?Carbon $paymentFinish);

    /**
     * @return string|null
     */
    abstract public function getPaymentReference(): ?string;

    /**
     * @param string|null $paymentReference
     *
     * @return self
     */
    abstract public function setPaymentReference(?string $paymentReference);

    /**
     * @return string|null
     */
    abstract public function getPaymentState(): ?string;

    /**
     * @param string|null $paymentState
     *
     * @return self
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
     * @return self
     */
    abstract public function setMessage(?string $message);

    /**
     * @return string|null
     */
    abstract public function getInternalPaymentId(): ?string;

    /**
     * @param string|null $internalPaymentId
     *
     * @return self
     */
    abstract public function setInternalPaymentId(?string $internalPaymentId);

    /**
     * @param string|null $providerData
     *
     * @return self
     */
    abstract public function setProviderData(?string $providerData);
}
