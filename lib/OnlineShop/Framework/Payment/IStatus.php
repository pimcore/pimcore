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


/**
 * Interface OnlineShop_Framework_Payment_IStatus
 */
interface OnlineShop_Framework_Payment_IStatus
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
    public function getPaymentReference();

    /**
     * pimcore internal payment id, necessary to identify payment information in order object
     *
     * @return string
     */
    public function getInternalPaymentId();

    /**
     * payment message provided from payment provider - e.g. error message on error
     *
     * @return string
     */
    public function getMessage();

    /**
     * internal pimcore order status - see also constants OnlineShop_Framework_AbstractOrder::ORDER_STATE_*
     *
     * @return string
     */
    public function getStatus();

    /**
     * additional payment data
     *
     * @return array
     */
    public function getData();
}