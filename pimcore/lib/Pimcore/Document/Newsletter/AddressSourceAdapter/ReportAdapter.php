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
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\Listing;

class ReportAdapter implements AddressSourceAdapterInterface
{

    /**
     * @var int
     */
    protected $reportId;

    /**
     * @var string[]
     */
    protected $emailFieldName;

    /**
     * @var string[]
     */
    protected $emailAddresses;

    /**
     * @var int
     */
    protected $elementsTotal;

    /**
     * @var Listing
     */
    protected $list;

    /**
     * IAddressSourceAdapter constructor.
     * @param $params
     */
    public function __construct($params)
    {
        $this->reportId = $params['reportId'];
        $this->emailFieldName = $params['emailFieldName'];
    }

    /**
     * @return Listing
     */
    protected function getListing()
    {
        $config = \Pimcore\Model\Tool\CustomReport\Config::getByName($this->reportId);
        $configuration = $config->getDataSourceConfig();
        $adapter = \Pimcore\Model\Tool\CustomReport\Config::getAdapter($configuration, $config);
        $result = $adapter->getData(null, $this->emailFieldName, 'ASC', null, null);

        $this->list = $result['data'];
        $this->elementsTotal = intval($result["total"]);

        $this->emailAddresses = [];
        foreach ($this->list as $row) {
            if (isset($row[$this->emailFieldName])) {
                $this->emailAddresses[] = $row[$this->emailFieldName];
            }
        }

        return $this->list;
    }

    /**
     * returns array of email addresses for batch sending
     *
     * @return SendingParamContainer[]
     */
    public function getMailAddressesForBatchSending()
    {
        if (!$this->list) {
            $this->getListing();
        }

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
     * @return SendingParamContainer
     */
    public function getParamsForTestSending($emailAddress)
    {
        if (!$this->list) {
            $this->getListing();
        }

        return new SendingParamContainer($emailAddress, current($this->list));
    }

    /**
     * returns total number of newsletter recipients
     *
     * @return int
     */
    public function getTotalRecordCount()
    {
        if (!$this->list) {
            $this->getListing();
        }

        return $this->elementsTotal;
    }

    /**
     * returns array of params to be set on mail for single sending
     *
     * @param $limit
     * @param $offset
     * @return SendingParamContainer[]
     */
    public function getParamsForSingleSending($limit, $offset)
    {
        if (!$this->list) {
            $this->getListing();
        }

        $listing = $this->list;

        $containers = [];

        for ($i = $offset; $i < ($offset + $limit); $i++) {
            if (isset($listing[$i][$this->emailFieldName])) {
                // as $listing is array type we can send all so every column can be used as placeholder in email
                $containers[] = new SendingParamContainer($listing[$i][$this->emailFieldName], $listing[$i]);
            }
        }

        return $containers;
    }
}
