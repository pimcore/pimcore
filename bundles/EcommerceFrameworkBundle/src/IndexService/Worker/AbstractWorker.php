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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Doctrine\DBAL\Connection;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractWorker implements WorkerInterface
{
    protected Connection $db;

    protected ConfigInterface $tenantConfig;

    protected string $name;

    protected ?array $indexColumns = null;

    protected ?array $filterGroups = null;

    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(ConfigInterface $tenantConfig, Connection $db, EventDispatcherInterface $eventDispatcher)
    {
        $this->tenantConfig = $tenantConfig;
        $tenantConfig->setTenantWorker($this);

        $this->name = $tenantConfig->getTenantName();
        $this->db = $db;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getTenantConfig(): ConfigInterface
    {
        return $this->tenantConfig;
    }

    public function getGeneralSearchAttributes(): array
    {
        return $this->tenantConfig->getSearchAttributes();
    }

    public function getIndexAttributes(bool $considerHideInFieldList = false): array
    {
        if (null === $this->indexColumns) {
            $indexColumns = [
                'categoryIds' => 'categoryIds',
            ];

            foreach ($this->tenantConfig->getAttributes() as $attribute) {
                if (!$considerHideInFieldList || !$attribute->getHideInFieldlistDatatype()) {
                    $indexColumns[$attribute->getName()] = $attribute->getName();
                }
            }

            $this->indexColumns = array_values($indexColumns);
        }

        return $this->indexColumns;
    }

    public function getIndexAttributesByFilterGroup(string $filterGroup): array
    {
        $this->getAllFilterGroups();

        return $this->filterGroups[$filterGroup] ?? [];
    }

    public function getAllFilterGroups(): array
    {
        if (null === $this->filterGroups) {
            $this->filterGroups = [];
            $this->filterGroups['system'] = array_diff($this->getSystemAttributes(), ['categoryIds']);
            $this->filterGroups['category'] = ['categoryIds'];

            foreach ($this->tenantConfig->getAttributes() as $attribute) {
                if (null !== $attribute->getFilterGroup()) {
                    $this->filterGroups[$attribute->getFilterGroup()][] = $attribute->getName();
                }
            }
        }

        return array_keys($this->filterGroups);
    }

    protected function getSystemAttributes(): array
    {
        return [];
    }

    /**
     * cleans up all old zombie data
     *
     * @param IndexableInterface $object
     * @param array $subObjectIds
     */
    protected function doCleanupOldZombieData(IndexableInterface $object, array $subObjectIds): void
    {
        $cleanupIds = $this->tenantConfig->getSubIdsToCleanup($object, $subObjectIds);
        foreach ($cleanupIds as $idToCleanup) {
            $this->doDeleteFromIndex($idToCleanup, $object);
        }
    }

    /**
     * actually deletes all sub entries from index. original object is delivered too, but keep in mind, that this might be empty.
     *
     * @param int $subObjectId
     * @param IndexableInterface|null $object - might be empty (when object doesn't exist any more in pimcore
     */
    abstract protected function doDeleteFromIndex(int $subObjectId, IndexableInterface $object = null): void;

    /**
     * Checks if given data is array and returns converted data suitable for search backend. For mysql it is a string with special delimiter.
     *
     * @param array|string $data
     *
     * @return array|string
     */
    protected function convertArray(array|string $data): array|string
    {
        if (is_array($data)) {
            return WorkerInterface::MULTISELECT_DELIMITER . implode(WorkerInterface::MULTISELECT_DELIMITER, $data) . WorkerInterface::MULTISELECT_DELIMITER;
        }

        return $data;
    }
}
