<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\NewsletterBundle\Document\Newsletter\AddressSourceAdapter;

use Pimcore\Bundle\NewsletterBundle\Document\Newsletter\AddressSourceAdapterInterface;
use Pimcore\Bundle\NewsletterBundle\Document\Newsletter\SendingParamContainer;

/**
 * @internal
 */
final class CsvList implements AddressSourceAdapterInterface
{
    /**
     * @var string[]
     */
    protected array $emailAddresses;

    /**
     * IAddressSourceAdapter constructor.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->emailAddresses = array_filter(explode(',', $params['csvList']));
    }

    /**
     * {@inheritdoc}
     */
    public function getMailAddressesForBatchSending(): array
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
    public function getParamsForTestSending(string $emailAddress): SendingParamContainer
    {
        return new SendingParamContainer($emailAddress, [
            'emailAddress' => current($this->emailAddresses),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalRecordCount(): int
    {
        return count($this->emailAddresses);
    }

    /**
     * {@inheritdoc}
     */
    public function getParamsForSingleSending(int $limit, int $offset): array
    {
        $addresses = array_slice($this->emailAddresses, $offset, $limit);

        $containers = [];
        foreach ($addresses as $address) {
            $containers[] = new SendingParamContainer($address, ['emailAddress' => $address]);
        }

        return $containers;
    }
}
