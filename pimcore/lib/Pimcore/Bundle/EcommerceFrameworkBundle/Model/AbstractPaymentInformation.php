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

/**
 * Abstract base class for payment information field collection
 */
abstract class AbstractPaymentInformation extends \Pimcore\Model\Object\Fieldcollection\Data\AbstractData
{

    /**
     * @return \DateTime
     */
    abstract public function getPaymentStart();

    /**
     * @param \DateTime $paymentStart
     * @return void
     */
    abstract public function setPaymentStart($paymentStart);

    /**
     * @return \DateTime
     */
    abstract public function getPaymentFinish();

    /**
     * @param \DateTime $paymentStart
     * @return void
     */
    abstract public function setPaymentFinish($paymentFinish);

    /**
     * @return string
     */
    abstract public function getPaymentReference();

    /**
     * @param string $paymentReference
     * @return void
     */
    abstract public function setPaymentReference($paymentReference);

    /**
     * @return string
     */
    abstract public function getPaymentState();

    /**
     * @param string $paymentState
     * @return void
     */
    abstract public function setPaymentState($paymentState);

    /**
     * @return string
     */
    abstract public function getMessage();

    /**
     * @return string
     */
    abstract public function getProviderData();

    /**
     * @param string $message
     * @return void
     */
    abstract public function setMessage($message);

    /**
     * @return string
     */
    abstract public function getInternalPaymentId();

    /**
     * @param string $internalPaymentId
     * @return void
     */
    abstract public function setInternalPaymentId($internalPaymentId);
}
