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

namespace Pimcore\Document\Newsletter\AddressSourceAdapter;

use Pimcore\Document\Newsletter\AddressSourceAdapterInterface;
use Pimcore\Document\Newsletter\SendingParamContainer;

class CsvList implements AddressSourceAdapterInterface
{
    /**
     * @var string[]
     */
    protected $emailAddresses;

    /**
     * IAddressSourceAdapter constructor.
     *
     * @param array $params
     */
    public function __construct($params)
    {
        $this->emailAddresses = array_filter(explode(',', $params['csvList']));
    }

    /**
     * returns array of email addresses for batch sending
     *
     * @return SendingParamContainer[]
     */
    public function getMailAddressesForBatchSending()
    {
        $containers = [];
        foreach ($this->emailAddresses as $address) {
            $containers[] = new SendingParamContainer($address, ['emailAddress' => $address]);
        }

        return $containers;
    }

    /**
     * returns params to be set on mail for test sending
     *
     * @param string $emailAddress
     *
     * @return SendingParamContainer
     */
    public function getParamsForTestSending($emailAddress)
    {
        return new SendingParamContainer($emailAddress, [
            'emailAddress' => current($this->emailAddresses),
        ]);
    }

    /**
     * returns total number of newsletter recipients
     *
     * @return int
     */
    public function getTotalRecordCount()
    {
        return count($this->emailAddresses);
    }

    /**
     * returns array of params to be set on mail for single sending
     *
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getParamsForSingleSending($limit, $offset)
    {
        $addresses = array_slice($this->emailAddresses, $offset, $limit);

        $containers = [];
        foreach ($addresses as $address) {
            $containers[] = new SendingParamContainer($address, ['emailAddress' => $address]);
        }

        return $containers;
    }
}
