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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Db\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractWorker implements WorkerInterface
{
    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var ConfigInterface
     */
    protected $tenantConfig;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $indexColumns;

    /**
     * @var array
     */
    protected $filterGroups;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(ConfigInterface $tenantConfig, ConnectionInterface $db, EventDispatcherInterface $eventDispatcher)
    {
        $this->tenantConfig = $tenantConfig;
        $tenantConfig->setTenantWorker($this);

        $this->name = $tenantConfig->getTenantName();
        $this->db = $db;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getTenantConfig()
    {
        return $this->tenantConfig;
    }

    public function getGeneralSearchAttributes()
    {
        return $this->tenantConfig->getSearchAttributes();
    }

    public function getIndexAttributes($considerHideInFieldList = false)
    {
        if (null === $this->indexColumns) {
            $indexColumns = [
                'categoryIds' => 'categoryIds',
            ];

            foreach ($this->tenantConfig->getAttributes() as $attribute) {
                if (!$considerHideInFieldList || ($considerHideInFieldList && !$attribute->getHideInFieldlistDatatype())) {
                    $indexColumns[$attribute->getName()] = $attribute->getName();
                }
            }

            $this->indexColumns = array_values($indexColumns);
        }

        return $this->indexColumns;
    }

    public function getIndexAttributesByFilterGroup($filterGroup)
    {
        $this->getAllFilterGroups();

        return $this->filterGroups[$filterGroup] ? $this->filterGroups[$filterGroup] : [];
    }

    public function getAllFilterGroups()
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

    protected function getSystemAttributes()
    {
        return [];
    }

    /**
     * cleans up all old zombie data
     *
     * @param IndexableInterface $object
     * @param array $subObjectIds
     */
    protected function doCleanupOldZombieData(IndexableInterface $object, array $subObjectIds)
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
    abstract protected function doDeleteFromIndex($subObjectId, IndexableInterface $object = null);

    /**
     * Checks if given data is array and returns converted data suitable for search backend. For mysql it is a string with special delimiter.
     *
     * @param array|string $data
     *
     * @return string
     */
    protected function convertArray($data)
    {
        if (is_array($data)) {
            return WorkerInterface::MULTISELECT_DELIMITER . implode(WorkerInterface::MULTISELECT_DELIMITER, $data) . WorkerInterface::MULTISELECT_DELIMITER;
        }

        return $data;
    }
}
