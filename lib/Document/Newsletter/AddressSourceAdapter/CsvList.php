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

namespace Pimcore\Document\Newsletter\AddressSourceAdapter;

use Pimcore\Document\Newsletter\AddressSourceAdapterInterface;
use Pimcore\Document\Newsletter\SendingParamContainer;

/**
 * @internal
 */
final class CsvList implements AddressSourceAdapterInterface
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getParamsForTestSending($emailAddress)
    {
        return new SendingParamContainer($emailAddress, [
            'emailAddress' => current($this->emailAddresses),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalRecordCount()
    {
        return count($this->emailAddresses);
    }

    /**
     * {@inheritdoc}
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
