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
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\RelationInterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Event\Ecommerce\IndexServiceEvents;
use Pimcore\Event\Model\Ecommerce\IndexService\PreprocessAttributeErrorEvent;
use Pimcore\Event\Model\Ecommerce\IndexService\PreprocessErrorEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Localizedfield;

/**
 * Provides worker functionality for batch preparing data and updating index
 *
 * @property AbstractConfig $tenantConfig
 *
 * @deprecated will be removed in Pimcore 7.0 use ProductCentricBatchProcessing instead
 * @TODO Pimcore 7 - remove this
 */
abstract class AbstractBatchProcessingWorker extends AbstractWorker implements BatchProcessingWorkerInterface
{
    const INDEX_STATUS_PREPARATION_STATUS_DONE = 0;
    const INDEX_STATUS_PREPARATION_STATUS_ERROR = 5;

    /**
     * returns name for store table
     *
     * @return string
     */
    abstract protected function getStoreTableName();

    /**
     * @param int $objectId
     * @param array|null $data
     * @param array|null $metadata
     */
    abstract protected function doUpdateIndex($objectId, $data = null, $metadata = null);

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
          `data` longtext CHARACTER SET latin1,
          `crc_current` bigint(11) DEFAULT NULL,
          `crc_index` bigint(11) DEFAULT NULL,
          `worker_timestamp` int(11) DEFAULT NULL,
          `worker_id` varchar(20) DEFAULT NULL,
          `in_preparation_queue` tinyint(1) DEFAULT NULL,
          `preparation_worker_timestamp` int(11) DEFAULT NULL,
          `preparation_worker_id` varchar(20) DEFAULT NULL,
          `update_status` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
          `update_error` CHAR(255) NULL DEFAULT NULL,
          `preparation_status` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
          `preparation_error` VARCHAR(255) NULL DEFAULT NULL,
          `trigger_info` VARCHAR(255) NULL DEFAULT NULL,
          `metadata` text,
          PRIMARY KEY (`o_id`,`tenant`),
          KEY `update_worker_index` (`tenant`,`crc_current`,`crc_index`,`worker_timestamp`),
          KEY `preparation_status_index` (`tenant`,`preparation_status`),
          KEY `in_preparation_queue_index` (`tenant`,`in_preparation_queue`),
          KEY `worker_id_index` (`worker_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /**
     * deletes element from store table
     *
     * @param int $objectId
     */
    protected function deleteFromStoreTable($objectId)
    {
        $this->db->deleteWhere($this->getStoreTableName(), 'o_id = ' . $this->db->quote((string)$objectId) . ' AND tenant = ' . $this->db->quote($this->name));
    }

    /**
     * prepare data for index creation and store is in store table
     *
     * @param IndexableInterface $object
     * @param int $subObjectId
     *
     * @return array
     */
    protected function getDefaultDataForIndex(IndexableInterface $object, $subObjectId)
    {
        $categories = $this->tenantConfig->getCategories($object, $subObjectId);
        $categoryIds = [];
        $parentCategoryIds = [];
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
     * @param IndexableInterface $object
     *
     * @return array returns the processed subobjects that can be used for the index update.
     */
    public function prepareDataForIndex(IndexableInterface $object)
    {
        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        $processedSubObjects = [];

        foreach ($subObjectIds as $subObjectId => $object) {
            /**
             * @var IndexableInterface $object
             */
            $insertData = [];
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

                $attributeErrors = [];
                foreach ($this->tenantConfig->getAttributes() as $attribute) {
                    try {
                        $value = $attribute->getValue($object, $subObjectId, $this->tenantConfig);

                        if (null !== $attribute->getInterpreter()) {
                            $value = $attribute->interpretValue($value);

                            if ($attribute->getInterpreter() instanceof RelationInterpreterInterface) {
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

                        if (array_key_exists($attribute->getName(), $data) && is_array($data[$attribute->getName()])) {
                            $data[$attribute->getName()] = $this->convertArray($data[$attribute->getName()]);
                        }
                    } catch (\Throwable $e) {
                        $event = new PreprocessAttributeErrorEvent($attribute, $e);
                        $event->setSubObjectId($subObjectId);
                        $this->eventDispatcher->dispatch(IndexServiceEvents::ATTRIBUTE_PROCESSING_ERROR, $event);

                        if ($event->doSkipAttribute()) {
                            Logger::err(
                                sprintf(
                                    'Exception in IndexService when processing the attribute "%s": %s',
                                    $event->getAttribute()->getName(),
                                    $event->getException()->getMessage()
                                )
                            );
                        } elseif ($event->doThrowException()) {
                            throw $e;
                        } else {
                            $attributeErrors[$attribute->getName()] = $e->getMessage();
                        }
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
                    'subtenants' => ($subTenantData ? $subTenantData : []),
                ]);

                $jsonLastError = \json_last_error();
                $generalErrors = [];
                if ($jsonLastError !== JSON_ERROR_NONE) {
                    $e = new \Exception("Could not encode product data for updating index. Json encode error code was {$jsonLastError}, ObjectId was {$subObjectId}.");
                    $event = new PreprocessErrorEvent($e);
                    $event->setSubObjectId($subObjectId);
                    $this->eventDispatcher->dispatch(IndexServiceEvents::GENERAL_PREPROCESSING_ERROR, $event);
                    if ($event->doThrowException()) {
                        throw $e;
                    } else {
                        $generalErrors[] = $e->getMessage();
                    }
                }

                $crc = crc32($jsonData);

                $preparationErrorDb = '';
                $hasError = !(count($attributeErrors) <= 0 && count($generalErrors) <= 0);

                if ($hasError) {
                    $preparationError = '';
                    if (count($generalErrors) > 0) {
                        $preparationError = implode(', ', $generalErrors);
                    }
                    if (count($attributeErrors) > 0) {
                        $preparationError .= 'Attribute errors: '.$preparationErrorDb = implode(',', array_keys($attributeErrors));
                    }

                    $preparationErrorDb = $preparationError;
                    if (strlen($preparationErrorDb) > 255) {
                        $preparationErrorDb = substr($preparationErrorDb, 0, 252).'...';
                    }
                }

                $insertData = [
                    'o_id' => $subObjectId,
                    'o_virtualProductId' => $data['o_virtualProductId'],
                    'tenant' => $this->name,
                    'data' => $jsonData,
                    'crc_current' => $crc,
                    'in_preparation_queue' => $hasError ? (int)true : (int)false,
                    'preparation_status' => $hasError ? self::INDEX_STATUS_PREPARATION_STATUS_ERROR : self::INDEX_STATUS_PREPARATION_STATUS_DONE,
                    'preparation_error' => $preparationErrorDb,
                ];

                if ($hasError) {
                    Logger::alert(sprintf('Mark product "%s" with preparation error.', $subObjectId),
                        array_merge($generalErrors, $attributeErrors)
                    );
                } else {
                    $processedSubObjects[$subObjectId] = $object;
                }
                $this->insertDataToIndex($insertData, $subObjectId);
            } else {
                Logger::info("Don't adding product " . $subObjectId . ' to index ' . $this->name . '.');
                $this->doDeleteFromIndex($subObjectId, $object);
            }
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);

        return $processedSubObjects;
    }

    /**
     * Inserts the data do the store table
     *
     * @param array $data
     * @param int $subObjectId
     */
    protected function insertDataToIndex($data, $subObjectId)
    {
        $currentEntry = $this->db->fetchRow('SELECT crc_current, in_preparation_queue FROM ' . $this->getStoreTableName() . ' WHERE o_id = ? AND tenant = ?', [$subObjectId, $this->name]);
        if (!$currentEntry) {
            $this->db->insert($this->getStoreTableName(), $data);
        } elseif ($currentEntry['crc_current'] != $data['crc_current']) {
            $this->executeTransactionalQuery(function () use ($data, $subObjectId) {
                $data['preparation_worker_timestamp'] = 0;
                $data['preparation_worker_id'] = null;

                $this->db->updateWhere($this->getStoreTableName(), $data, 'o_id = ' . $this->db->quote((string)$subObjectId) . ' AND tenant = ' . $this->db->quote($this->name));
            });
        } elseif ($currentEntry['in_preparation_queue']) {

            //since no data has changed, just update flags, not data
            $this->executeTransactionalQuery(function () use ($subObjectId) {
                $this->db->query('UPDATE ' . $this->getStoreTableName() . ' SET in_preparation_queue = 0, preparation_worker_timestamp = 0, preparation_worker_id = null WHERE o_id = ? AND tenant = ?', [$subObjectId, $this->name]);
            });
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
     * @param IndexableInterface $object
     *
     * @throws \Exception
     */
    public function fillupPreparationQueue(IndexableInterface $object)
    {
        if ($object instanceof Concrete) {

            //need check, if there are sub objects because update on empty result set is too slow
            $objects = $this->db->fetchCol('SELECT o_id FROM objects WHERE o_path LIKE ?', [$this->db->escapeLike($object->getFullPath()) . '/%']);
            if ($objects) {
                $this->executeTransactionalQuery(function () use ($objects) {
                    $updateStatement = 'UPDATE ' . $this->getStoreTableName() . ' SET in_preparation_queue = 1 WHERE tenant = ? AND o_id IN ('.implode(',', $objects).')';
                    $this->db->query($updateStatement, [$this->name]);
                });
            }
        }
    }

    /**
     * @deprecated will be removed in Pimcore 7.0
     * @TODO Pimcore 7 - remove this
     *
     * processes elements in the queue for preparation of index data
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     *
     * @return int number of entries
     */
    public function processPreparationQueue($limit = 200)
    {
        @trigger_error(
            'Method AbstractBatchProcessingWorker::processPrepartionQueue is deprecated since version 6.7.0 and will be removed in 7.0.0. ' .
            'Use ecommerce:indexservice:process-preparation-queue command instead.',
            E_USER_DEPRECATED
        );

        $workerId = uniqid();
        $workerTimestamp = time();
        $this->db->query(
            'UPDATE ' . $this->getStoreTableName() . ' SET preparation_worker_id = ?, preparation_worker_timestamp = ? WHERE tenant = ? AND in_preparation_queue = 1 '
           .'AND (ISNULL(preparation_worker_timestamp) OR preparation_worker_timestamp < ?) ORDER BY preparation_status ASC LIMIT ' . intval($limit),
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
                if ($object instanceof IndexableInterface) {
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
     * @deprecated will be removed in Pimcore 7.0
     * @TODO Pimcore 7 - remove this
     *
     * processes the update index queue - updates all elements where current_crc != index_crc
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     *
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200)
    {
        @trigger_error(
            'Method AbstractBatchProcessingWorker::processUpdateIndexQueue is deprecated since version 6.7.0 and will be removed in 7.0.0. ' .
            'Use ecommerce:indexservice:process-update-queue command instead.',
            E_USER_DEPRECATED
        );

        $workerId = uniqid();
        $workerTimestamp = time();
        $entries = [];

        try {

            //fetch open IDs and assign a worker-ID. SELECT and Update statements are separated, as a combined
            //statement can take several seconds on large-scale systems.

            $this->db->beginTransaction();
            $query = "SELECT o_id, data, metadata FROM {$this->getStoreTableName()}
                  WHERE (crc_current != crc_index OR ISNULL(crc_index)) AND tenant = ? AND (ISNULL(worker_timestamp) OR worker_timestamp < ?) LIMIT "
                . intval($limit) . ' FOR UPDATE';

            $entries = $this->db->fetchAll($query, [$this->name, $workerTimestamp - $this->getWorkerTimeout()]);

            if (count($entries) > 0) {
                $queueIds = array_map(function ($e) {
                    return $e['o_id'];
                }, $entries);
                $ids = implode(',', $queueIds);
                $updateQuery = "UPDATE {$this->getStoreTableName()} SET worker_id = ?, worker_timestamp = ? WHERE o_id in ({$ids}) and tenant=?";
                $this->db->query($updateQuery, [$workerId, $workerTimestamp, $this->name]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            Logger::warn('Error during processUpdateIndexQueue().');
            try {
                $this->db->rollBack();
            } catch (\Exception $e) {
                Logger::error('Error on rollback in processUpdateIndexQueue().');
            }

            return 0;
        }

        //process entries (outside transaction, as worker ID is secured).
        foreach ($entries as $entry) {
            Logger::info("Worker $workerId updating index for element " . $entry['o_id']);
            $data = json_decode($entry['data'], true);
            $this->doUpdateIndex($entry['o_id'], $data, $entry['metadata']);
        }

        return count($entries);
    }

    /**
     * resets the store table by marking all items as "in preparation", so items in store will be regenerated
     */
    public function resetPreparationQueue()
    {
        Logger::info('Index-Actions - Resetting preparation queue');
        $className = (new \ReflectionClass($this))->getShortName();
        $query = 'UPDATE '. $this->getStoreTableName() ." SET worker_timestamp = null,
                        worker_id = null,
                        preparation_worker_timestamp = 0,
                        preparation_worker_id = null,
                        preparation_status = '',
                        preparation_error = '',
                        trigger_info = ?,
                        in_preparation_queue = 1 WHERE tenant = ?";
        $this->db->query($query, [
            sprintf('Reset preparation queue in "%s".', $className),
            $this->name,
        ]);
    }

    /**
     * resets the store table to initiate a re-indexing
     */
    public function resetIndexingQueue()
    {
        Logger::info('Index-Actions - Resetting index queue');
        $className = (new \ReflectionClass($this))->getShortName();
        $query = 'UPDATE '. $this->getStoreTableName() .' SET worker_timestamp = null,
                        worker_id = null,
                        preparation_worker_timestamp = 0,
                        preparation_worker_id = null,
                        trigger_info = ?,
                        crc_index = 0 WHERE tenant = ?';
        $this->db->query($query, [
            sprintf('Reset indexing queue in "%s".', $className),
            $this->name,
        ]);
    }

    /**
     * @param \Closure $fn
     * @param int $maxTries
     * @param float $sleep
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function executeTransactionalQuery(\Closure $fn, int $maxTries = 3, float $sleep = .5)
    {
        $this->db->beginTransaction();
        for ($i = 1; $i <= $maxTries; $i++) {
            try {
                $fn();

                return $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
                Logger::warning("Executing transational query, no. {$i} of {$maxTries} tries failed. " . $e->getMessage());
                if ($i === $maxTries) {
                    throw $e;
                }
                usleep($sleep * 1000000);
            }
        }

        return false;
    }
}
