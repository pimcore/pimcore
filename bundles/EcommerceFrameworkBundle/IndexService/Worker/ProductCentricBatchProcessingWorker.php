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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\RelationInterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Event\Ecommerce\IndexServiceEvents;
use Pimcore\Event\Model\Ecommerce\IndexService\PreprocessAttributeErrorEvent;
use Pimcore\Event\Model\Ecommerce\IndexService\PreprocessErrorEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Localizedfield;

abstract class ProductCentricBatchProcessingWorker extends AbstractWorker implements BatchProcessingWorkerInterface
{
    const INDEX_STATUS_PREPARATION_STATUS_DONE = 0;
    const INDEX_STATUS_PREPARATION_STATUS_ERROR = 5;

    /**
     * returns name for store table
     *
     * @return string
     */
    abstract protected function getStoreTableName();

    public function getBatchProcessingStoreTableName(): string
    {
        return $this->getStoreTableName();
    }

    /**
     * @param int $objectId
     * @param array|null $data
     * @param array|null $metadata
     */
    abstract protected function doUpdateIndex($objectId, $data = null, $metadata = null);

    public function updateItemInIndex($objectId): void
    {
        $this->doUpdateIndex($objectId);
    }

    public function commitBatchToIndex(): void
    {
        //nothing to do by default
    }

    /**
     * creates store table
     */
    protected function createOrUpdateStoreTable()
    {
        $primaryIdColumnType = $this->tenantConfig->getIdColumnType(true);
        $idColumnType = $this->tenantConfig->getIdColumnType(false);

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $this->getBatchProcessingStoreTableName() . "` (
          `o_id` $primaryIdColumnType,
          `o_virtualProductId` $idColumnType,
          `tenant` varchar(50) NOT NULL DEFAULT '',
          `data` longtext CHARACTER SET latin1,
          `crc_current` bigint(11) DEFAULT NULL,
          `crc_index` bigint(11) DEFAULT NULL,
          `in_preparation_queue` tinyint(1) DEFAULT NULL,
          `update_status` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
          `update_error` CHAR(255) NULL DEFAULT NULL,
          `preparation_status` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
          `preparation_error` VARCHAR(255) NULL DEFAULT NULL,
          `trigger_info` VARCHAR(255) NULL DEFAULT NULL,
          `metadata` text,
          PRIMARY KEY (`o_id`,`tenant`),
          KEY `update_worker_index` (`tenant`,`crc_current`,`crc_index`),
          KEY `preparation_status_index` (`tenant`,`preparation_status`),
          KEY `in_preparation_queue_index` (`tenant`,`in_preparation_queue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
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
                $this->db->updateWhere($this->getStoreTableName(), $data, 'o_id = ' . $this->db->quote((string)$subObjectId) . ' AND tenant = ' . $this->db->quote($this->name));
            });
        } elseif ($currentEntry['in_preparation_queue']) {

            //since no data has changed, just update flags, not data
            $this->executeTransactionalQuery(function () use ($subObjectId) {
                $this->db->query('UPDATE ' . $this->getStoreTableName() . ' SET in_preparation_queue = 0 WHERE o_id = ? AND tenant = ?', [$subObjectId, $this->name]);
            });
        }
    }

    protected function getWorkerTimeout()
    {
        return 300;
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
        if ($object->getOSIndexType() == ProductListInterface::PRODUCT_TYPE_VARIANT) {
            $virtualProductId = $this->tenantConfig->createVirtualParentIdForSubId($object, $subObjectId);
        }

        $virtualProduct = DataObject::getById($virtualProductId);
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
     * {@inheritdoc}
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
                $b = DataObject::doGetInheritedValues();
                \Pimcore::unsetAdminMode();
                DataObject::setGetInheritedValues(true);
                $hidePublishedMemory = DataObject::doHideUnpublished();
                DataObject::setHideUnpublished(false);
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
                        $this->eventDispatcher->dispatch($event, IndexServiceEvents::ATTRIBUTE_PROCESSING_ERROR);

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
                DataObject::setGetInheritedValues($b);
                DataObject::setHideUnpublished($hidePublishedMemory);
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
                    $this->eventDispatcher->dispatch($event, IndexServiceEvents::GENERAL_PREPROCESSING_ERROR);
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
     * {@inheritdoc}
     */
    public function resetPreparationQueue()
    {
        Logger::info('Index-Actions - Resetting preparation queue');
        $className = (new \ReflectionClass($this))->getShortName();
        $query = 'UPDATE '. $this->getStoreTableName() ." SET
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
     * {@inheritdoc}
     */
    public function resetIndexingQueue()
    {
        Logger::info('Index-Actions - Resetting index queue');
        $className = (new \ReflectionClass($this))->getShortName();
        $query = 'UPDATE '. $this->getStoreTableName() .' SET
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
