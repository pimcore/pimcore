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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model;

/**
 * Abstract base class for payment information field collection
 */
abstract class AbstractPaymentInformation extends \Pimcore\Model\Object\Fieldcollection\Data\AbstractData {

    /**
     * @return \DateTime
     */
    public abstract function getPaymentStart ();

    /**
     * @param \DateTime $paymentStart
     * @return void
     */
    public abstract function setPaymentStart ($paymentStart);

    /**
     * @return \DateTime
     */
    public abstract function getPaymentFinish ();

    /**
     * @param \DateTime $paymentStart
     * @return void
     */
    public abstract function setPaymentFinish ($paymentFinish);

    /**
     * @return string
     */
    public abstract function getPaymentReference ();

    /**
     * @param string $paymentReference
     * @return void
     */
    public abstract function setPaymentReference ($paymentReference);

    /**
     * @return string
     */
    public abstract function getPaymentState ();

    /**
     * @param string $paymentState
     * @return void
     */
    public abstract function setPaymentState ($paymentState);

    /**
     * @return string
     */
    public abstract function getMessage ();

    /**
     * @return string
     */
    public abstract function getProviderData();

    /**
     * @param string $message
     * @return void
     */
    public abstract function setMessage ($message);

    /**
     * @return string
     */
    public abstract function getInternalPaymentId ();

    /**
     * @param string $internalPaymentId
     * @return void
     */
    public abstract function setInternalPaymentId ($internalPaymentId);

}
