<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;

use Exception;
use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use Pimcore\Model\Listing\AbstractListing;

/**
 * @internal
 *
 * @method Data\Listing\Dao getDao()
 * @method Data[] load()
 * @method Data|false current()
 * @method int getTotalCount()
 */
class Listing extends AbstractListing
{
    /**
     * @return Data[]
     */
    public function getEntries(): array
    {
        return $this->getData();
    }

    /**
     * @param Data[]|null $entries
     *
     * @return $this
     */
    public function setEntries(?array $entries): static
    {
        return $this->setData($entries);
    }

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->initDao(__CLASS__);
    }

    public function isValidOrderKey(string $key): bool
    {
        return in_array(
            $key,
            [
                'type',
                'id',
                'key',
                'index',
                'fullpath',
                'maintype',
                'subtype',
                'published',
                'creationDate',
                'modificationDate',
                'userOwner',
                'userModification',
                'data',
                'properties',
            ]
        );
    }
}
