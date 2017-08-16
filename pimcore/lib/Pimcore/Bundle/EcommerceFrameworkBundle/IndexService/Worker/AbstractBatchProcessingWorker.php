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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\AbstractConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IRelationInterpreter;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Logger;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Object\Localizedfield;

/**
 * Provides worker functionality for batch preparing data and updating index
 *
 * @property AbstractConfig $tenantConfig
 */
abstract class AbstractBatchProcessingWorker extends AbstractWorker implements IBatchProcessingWorker
{
    /**
     * returns name for store table
     *
     * @return string
     */
    abstract protected function getStoreTableName();

    /**
     * @param $objectId
     * @param null $data
     */
    abstract protected function doUpdateIndex($objectId, $data = null);

    /**
     * creates store table
     */
    protected function createOrUpdateStoreTable()
    {
        $primaryIdColumnType = $this->tenantConfig->getIdColumnType(true);
        $idColumnType = $this->tenantConfig->getIdColumnType(false);

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $this->getStoreTableName() . "` (
          `o_id` $primaryIdColumnType,
          `o_virtualProductId` $idColumnType,
          `tenant` varchar(50) NOT NULL DEFAULT '',
          `data` text CHARACTER SET latin1,
          `crc_current` bigint(11) DEFAULT NULL,
          `crc_index` bigint(11) DEFAULT NULL,
          `worker_timestamp` int(11) DEFAULT NULL,
          `worker_id` varchar(20) DEFAULT NULL,
          `in_preparation_queue` tinyint(1) DEFAULT NULL,
          `preparation_worker_timestamp` int(11) DEFAULT NULL,
          `preparation_worker_id` varchar(20) DEFAULT NULL,
          `update_status` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
          `update_error` CHAR(255) NULL DEFAULT NULL,
          PRIMARY KEY (`o_id`,`tenant`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /**
     * deletes element from store table
     *
     * @param $objectId
     */
    protected function deleteFromStoreTable($objectId)
    {
        $this->db->deleteWhere($this->getStoreTableName(), 'o_id = ' . $this->db->quote((string)$objectId) . ' AND tenant = ' . $this->db->quote($this->name));
    }

    /**
     * prepare data for index creation and store is in store table
     *
     * @param IIndexable $object
     * @param $subObjectId
     *
     * @return array
     */
    protected function getDefaultDataForIndex(IIndexable $object, $subObjectId)
    {
        $categories = $object->getCategories();
        $categoryIds = [];
        $parentCategoryIds =[];
        $categoryIdPaths = [];
        if ($categories) {
            foreach ($categories as $c) {
                if ($c instanceof AbstractCategory) {
                    $categoryIds[$c->getId()] = $c->getId();
                }

                $currentCategory = $c;
                while ($currentCategory instanceof AbstractCategory) {
                    $parentCategoryIds[$currentCategory->getId()] = $currentCategory->getId();

                    if ($currentCategory->getOSProductsInParentCategoryVisible()) {
                        $currentCategory = $currentCategory->getParent();
                    } else {
                        $currentCategory = null;
                    }
                }

                $tmpIds = [];
                $workingCategory = $c;
                while ($workingCategory) {
                    $tmpIds[] = $workingCategory->getId();
                    $workingCategory = $workingCategory->getParent();
                    if (!$workingCategory instanceof  AbstractCategory) {
                        break;
                    }
                }
                $tmpIds = array_reverse($tmpIds);
                $s = '';
                foreach ($tmpIds as $id) {
                    $s .= '/'.$id;
                    $categoryIdPaths[] = $s;
                }
            }
        }
        $categoryIdPaths = (array)array_unique($categoryIdPaths);
        sort($categoryIdPaths);
        ksort($categoryIds);

        $virtualProductId = $subObjectId;
        $virtualProductActive = $object->isActive();
        if ($object->getOSIndexType() == 'variant') {
            $virtualProductId = $this->tenantConfig->createVirtualParentIdForSubId($object, $subObjectId);
        }

        $virtualProduct = AbstractObject::getById($virtualProductId);
        if ($virtualProduct && method_exists($virtualProduct, 'isActive')) {
            $virtualProductActive = $virtualProduct->isActive();
        }

        $data = [
            'o_id' => $subObjectId,
            'o_classId' => $object->getClassId(),
            'o_virtualProductId' => $virtualProductId,
            'o_virtualProductActive' => $virtualProductActive,
            'o_parentId' => $object->getOSParentId(),
            'o_type' => $object->getOSIndexType(),
            'categoryIds' => ',' . implode(',', $categoryIds) . ',',
            'parentCategoryIds' => ',' . implode(',', $parentCategoryIds) . ',',
            'categoryPaths' => (array)$categoryIdPaths,
            'priceSystemName' => $object->getPriceSystemName(),
            'active' => $object->isActive(),
            'inProductList' => $object->isActive(true),
            'tenant' => $this->name,
        ];

        return $data;
    }

    /**
     * prepare data for index creation and store is in store table
     *
     * @param IIndexable $object
     */
    public function prepareDataForIndex(IIndexable $object)
    {
        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach ($subObjectIds as $subObjectId => $object) {
            /**
             * @var IIndexable $object
             */
            if ($object->getOSDoIndexProduct() && $this->tenantConfig->inIndex($object)) {
                $a = \Pimcore::inAdmin();
                $b = AbstractObject::doGetInheritedValues();
                \Pimcore::unsetAdminMode();
                AbstractObject::setGetInheritedValues(true);
                $hidePublishedMemory = AbstractObject::doHideUnpublished();
                AbstractObject::setHideUnpublished(false);
                $getFallbackLanguagesMemory = Localizedfield::getGetFallbackValues();
                Localizedfield::setGetFallbackValues(true);

                $data = $this->getDefaultDataForIndex($object, $subObjectId);
                $relationData = [];

                foreach ($this->tenantConfig->getAttributes() as $attribute) {
                    try {
                        $value = $attribute->getValue($object, $subObjectId, $this->tenantConfig);

                        if (null !== $attribute->getInterpreter()) {
                            $value = $attribute->interpretValue($value);

                            if ($attribute->getInterpreter() instanceof IRelationInterpreter) {
                                foreach ($value as $v) {
                                    $relData = [];
                                    $relData['src'] = $subObjectId;
                                    $relData['src_virtualProductId'] = $data['o_virtualProductId'];
                                    $relData['dest'] = $v['dest'];
                                    $relData['fieldname'] = $attribute->getName();
                                    $relData['type'] = $v['type'];
                                    $relationData[] = $relData;
                                }
                            } else {
                                $data[$attribute->getName()] = $value;
                            }
                        } else {
                            $data[$attribute->getName()] = $value;
                        }

                        if (is_array($data[$attribute->getName()])) {
                            $data[$attribute->getName()] = $this->convertArray($data[$attribute->getName()]);
                        }
                    } catch (\Exception $e) {
                        Logger::err('Exception in IndexService: ' . $e);
                    }
                }

                if ($a) {
                    \Pimcore::setAdminMode();
                }
                AbstractObject::setGetInheritedValues($b);
                AbstractObject::setHideUnpublished($hidePublishedMemory);
                Localizedfield::setGetFallbackValues($getFallbackLanguagesMemory);

                $subTenantData = $this->tenantConfig->prepareSubTenantEntries($object, $subObjectId);
                $jsonData = json_encode([
                    'data' => $data,
                    'relations' => ($relationData ? $relationData : []),
                    'subtenants' => ($subTenantData ? $subTenantData : [])
                ]);

                $crc = crc32($jsonData);
                $insertData = [
                    'o_id' => $subObjectId,
                    'o_virtualProductId' => $data['o_virtualProductId'],
                    'tenant' => $this->name,
                    'data' => $jsonData,
                    'crc_current' => $crc,
                    'preparation_worker_timestamp' => 0,
                    'preparation_worker_id' => $this->db->quote(null),
                    'in_preparation_queue' => 0
                ];

                $this->insertDataToIndex($insertData, $subObjectId);
            } else {
                Logger::info("Don't adding product " . $subObjectId . ' to index ' . $this->name . '.');
                $this->doDeleteFromIndex($subObjectId, $object);
            }
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);
    }

    /**
     * Inserts the data do the store table
     *
     * @param $data
     * @param $subObjectId
     */
    protected function insertDataToIndex($data, $subObjectId)
    {
        $currentEntry = $this->db->fetchRow('SELECT crc_current, in_preparation_queue FROM ' . $this->getStoreTableName() . ' WHERE o_id = ? AND tenant = ?', [$subObjectId, $this->name]);
        if (!$currentEntry) {
            $this->db->insert($this->getStoreTableName(), $data);
        } elseif ($currentEntry['crc_current'] != $data['crc_current']) {
            $this->db->updateWhere($this->getStoreTableName(), $data, 'o_id = ' . $this->db->quote((string)$subObjectId) . ' AND tenant = ' . $this->db->quote($this->name));
        } elseif ($currentEntry['in_preparation_queue']) {
            $this->db->query('UPDATE ' . $this->getStoreTableName() . ' SET in_preparation_queue = 0, preparation_worker_timestamp = 0, preparation_worker_id = null WHERE o_id = ? AND tenant = ?', [$subObjectId, $this->name]);
        }
    }

    protected function getWorkerTimeout()
    {
        return 300;
    }

    // ==============================================================
    // methods for batch preparing data and updating index
    // ==============================================================

    /**
     * fills queue based on path
     *
     * @param IIndexable $object
     */
    public function fillupPreparationQueue(IIndexable $object)
    {
        if ($object instanceof Concrete) {

            //need check, if there are sub objects because update on empty result set is too slow
            $objects = $this->db->fetchCol('SELECT o_id FROM objects WHERE o_path LIKE ?', [$object->getFullPath() . '/%']);
            if ($objects) {
                $updateStatement = 'UPDATE ' . $this->getStoreTableName() . ' SET in_preparation_queue = 1 WHERE tenant = ? AND o_id IN ('.implode(',', $objects).')';
                $this->db->query($updateStatement, [$this->name]);
            }
        }
    }

    /**
     * processes elements in the queue for preparation of index data
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     *
     * @return int number of entries
     */
    public function processPreparationQueue($limit = 200)
    {
        $workerId = uniqid();
        $workerTimestamp = time();
        $this->db->query(
            'UPDATE ' . $this->getStoreTableName() . ' SET preparation_worker_id = ?, preparation_worker_timestamp = ? WHERE tenant = ? AND in_preparation_queue = 1 AND (ISNULL(preparation_worker_timestamp) OR preparation_worker_timestamp < ?) LIMIT ' . intval($limit),
            [$workerId, $workerTimestamp, $this->name, $workerTimestamp - $this->getWorkerTimeout()]
        );

        $entries = $this->db->fetchCol(
            'SELECT o_id FROM ' . $this->getStoreTableName() . ' WHERE preparation_worker_id = ?',
            [$workerId]
        );

        if ($entries) {
            foreach ($entries as $objectId) {
                Logger::info("Worker $workerId preparing data for index for element " . $objectId);

                $object = $this->tenantConfig->getObjectById($objectId, true);
                if ($object instanceof IIndexable) {
                    $this->prepareDataForIndex($object);
                } else {
                    //delete entry with id which was retrieved from index before
                    Logger::warn("Element with ID $objectId in product index but cannot be found in pimcore -> deleting element from index.");
                    $this->doDeleteFromIndex($objectId, $object);
                }
            }

            return count($entries);
        } else {
            return 0;
        }
    }

    /**
     * processes the update index queue - updates all elements where current_crc != index_crc
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     *
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200)
    {
        $workerId = uniqid();
        $workerTimestamp = time();
        $this->db->query(
            'UPDATE ' . $this->getStoreTableName() . ' SET worker_id = ?, worker_timestamp = ? WHERE (crc_current != crc_index OR ISNULL(crc_index)) AND tenant = ? AND (ISNULL(worker_timestamp) OR worker_timestamp < ?) LIMIT ' . intval($limit),
            [$workerId, $workerTimestamp, $this->name, $workerTimestamp - $this->getWorkerTimeout()]
        );

        $entries = $this->db->fetchAll('SELECT o_id, data FROM ' . $this->getStoreTableName() . ' WHERE worker_id = ?', [$workerId]);

        if ($entries) {
            foreach ($entries as $entry) {
                Logger::info("Worker $workerId updating index for element " . $entry['id']);
                $data = json_decode($entry['data'], true);
                $this->doUpdateIndex($entry['o_id'], $data);
            }

            return count($entries);
        } else {
            return 0;
        }
    }

    /**
     * resets the store table by marking all items as "in preparation", so items in store will be regenerated
     */
    public function resetPreparationQueue()
    {
        Logger::info('Index-Actions - Resetting preparation queue');
        $query = 'UPDATE '. $this->getStoreTableName() .' SET worker_timestamp = null,
                        worker_id = null,
                        preparation_worker_timestamp = 0,
                        preparation_worker_id = null,
                        in_preparation_queue = 1 WHERE tenant = ?';
        $this->db->query($query, [$this->name]);
    }

    /**
     * resets the store table to initiate a re-indexing
     */
    public function resetIndexingQueue()
    {
        Logger::info('Index-Actions - Resetting index queue');
        $query = 'UPDATE '. $this->getStoreTableName() .' SET worker_timestamp = null,
                        worker_id = null,
                        preparation_worker_timestamp = 0,
                        preparation_worker_id = null,
                        crc_index = 0 WHERE tenant = ?';
        $this->db->query($query, [$this->name]);
    }
}
