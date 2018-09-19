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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Db\Connection;

abstract class AbstractWorker implements IWorker
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var IConfig
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

    public function __construct(IConfig $tenantConfig, Connection $db)
    {
        $this->tenantConfig = $tenantConfig;
        $tenantConfig->setTenantWorker($this);

        $this->name = $tenantConfig->getTenantName();
        $this->db = $db;
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
                'categoryIds' => 'categoryIds'
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
     * @param IIndexable $object
     * @param array $subObjectIds
     */
    protected function doCleanupOldZombieData(IIndexable $object, array $subObjectIds)
    {
        $cleanupIds = $this->tenantConfig->getSubIdsToCleanup($object, $subObjectIds);
        foreach ($cleanupIds as $idToCleanup) {
            $this->doDeleteFromIndex($idToCleanup, $object);
        }
    }

    /**
     * actually deletes all sub entries from index. original object is delivered too, but keep in mind, that this might be empty.
     *
     * @param $subObjectId
     * @param IIndexable $object - might be empty (when object doesn't exist any more in pimcore
     *
     * @return mixed
     */
    abstract protected function doDeleteFromIndex($subObjectId, IIndexable $object = null);

    /**
     * Checks if given data is array and returns converted data suitable for search backend. For mysql it is a string with special delimiter.
     *
     * @param $data
     *
     * @return string
     */
    protected function convertArray($data)
    {
        if (is_array($data)) {
            return IWorker::MULTISELECT_DELIMITER . implode($data, IWorker::MULTISELECT_DELIMITER) . IWorker::MULTISELECT_DELIMITER;
        }

        return $data;
    }
}
