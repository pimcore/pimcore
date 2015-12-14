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

namespace OnlineShop\Framework\Model;

/**
 * Abstract base class for payment information field collection
 */
abstract class AbstractPaymentInformation extends \Pimcore\Model\Object\Fieldcollection\Data\AbstractData {

    /**
     * @return \Zend_Date
     */
    public abstract function getPaymentStart ();

    /**
     * @param \Zend_Date $paymentStart
     * @return void
     */
    public abstract function setPaymentStart ($paymentStart);

    /**
     * @return \Zend_Date
     */
    public abstract function getPaymentFinish ();

    /**
     * @param \Zend_Date $paymentStart
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
