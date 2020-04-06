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

namespace Pimcore\Document\Newsletter;

interface AddressSourceAdapterInterface
{
    /**
     * returns array of email addresses for batch sending
     *
     * @return SendingParamContainer[]
     */
    public function getMailAddressesForBatchSending();

    /**
     * returns params to be set on mail for test sending
     *
     * @param string $emailAddress
     *
     * @return SendingParamContainer
     */
    public function getParamsForTestSending($emailAddress);

    /**
     * returns total number of newsletter recipients
     *
     * @return int
     */
    public function getTotalRecordCount();

    /**
     * returns array of params to be set on mail for single sending
     *
     * @param int $limit
     * @param int $offset
     *
     * @return SendingParamContainer[]
     */
    public function getParamsForSingleSending($limit, $offset);
}
