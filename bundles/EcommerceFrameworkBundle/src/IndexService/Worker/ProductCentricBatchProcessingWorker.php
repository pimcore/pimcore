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

use Pimcore\Bundle\EcommerceFrameworkBundle\Event\IndexServiceEvents;
use Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\IndexService\PreprocessAttributeErrorEvent;
use Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\IndexService\PreprocessErrorEvent;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\RelationInterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Db\Helper;
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
     */
    abstract protected function getStoreTableName(): string;

    public function getBatchProcessingStoreTableName(): string
    {
        return $this->getStoreTableName();
    }

    abstract protected function doUpdateIndex(int $objectId, array $data = null, array $metadata = null): void;

    public function updateItemInIndex(int $objectId): void
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
    protected function createOrUpdateStoreTable(): void
    {
        $primaryIdColumnType = $this->tenantConfig->getIdColumnType(true);
        $idColumnType = $this->tenantConfig->getIdColumnType(false);

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS `' . $this->getBatchProcessingStoreTableName() . "` (
          `id` $primaryIdColumnType,
          `virtualProductId` $idColumnType,
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
          PRIMARY KEY (`id`,`tenant`),
          KEY `update_worker_index` (`tenant`,`crc_current`,`crc_index`),
          KEY `preparation_status_index` (`tenant`,`preparation_status`),
          KEY `in_preparation_queue_index` (`tenant`,`in_preparation_queue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /**
     * Inserts the data do the store table
     */
    protected function insertDataToIndex(array $data, int $subObjectId): void
    {
        $currentEntry = $this->db->fetchAssociative('SELECT crc_current, in_preparation_queue FROM ' . $this->getStoreTableName() . ' WHERE id = ? AND tenant = ?', [$subObjectId, $this->name]);
        if (!$currentEntry) {
            $this->db->insert($this->getStoreTableName(), $data);
        } elseif ($currentEntry['crc_current'] != $data['crc_current']) {
            $this->executeTransactionalQuery(function () use ($data, $subObjectId) {
                $this->db->update($this->getStoreTableName(), $data, ['id' => (string)$subObjectId, 'tenant' => $this->name]);
            });
        } elseif ($currentEntry['in_preparation_queue']) {
            //since no data has changed, just update flags, not data
            $this->executeTransactionalQuery(function () use ($subObjectId) {
                $this->db->executeQuery('UPDATE ' . $this->getStoreTableName() . ' SET in_preparation_queue = 0 WHERE id = ? AND tenant = ?', [$subObjectId, $this->name]);
            });
        }
    }

    protected function getWorkerTimeout(): int
    {
        return 300;
    }

    /**
     * deletes element from store table
     */
    protected function deleteFromStoreTable(int $objectId): void
    {
        $this->db->delete($this->getStoreTableName(), ['id' => (string)$objectId, 'tenant' => $this->name]);
    }

    /**
     * fills queue based on path
     *
     * @throws \Exception
     */
    public function fillupPreparationQueue(IndexableInterface $object): void
    {
        if ($object instanceof Concrete) {
            //need check, if there are sub objects because update on empty result set is too slow
            $objects = $this->db->fetchFirstColumn('SELECT id FROM objects WHERE `path` like ?', [Helper::escapeLike($object->getFullPath()) . '/%']);
            if ($objects) {
                $this->executeTransactionalQuery(function () use ($objects) {
                    $updateStatement = 'UPDATE ' . $this->getStoreTableName() . ' SET in_preparation_queue = 1 WHERE tenant = ? AND id IN ('.implode(',', $objects).')';
                    $this->db->executeQuery($updateStatement, [$this->name]);
                });
            }
        }
    }

    /**
     * prepare data for index creation and store is in store table
     */
    protected function getDefaultDataForIndex(IndexableInterface $object, int $subObjectId): array
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
            'id' => $subObjectId,
            'classId' => $object->getClassId(),
            'virtualProductId' => $virtualProductId,
            'virtualProductActive' => $virtualProductActive,
            'parentId' => $object->getOSParentId(),
            'type' => $object->getOSIndexType(),
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
    public function prepareDataForIndex(IndexableInterface $object): array
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
                                    $relData['src_virtualProductId'] = $data['virtualProductId'];
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
                    'id' => $subObjectId,
                    'virtualProductId' => $data['virtualProductId'],
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
    public function resetPreparationQueue(): void
    {
        Logger::info('Index-Actions - Resetting preparation queue');
        $className = (new \ReflectionClass($this))->getShortName();
        $query = 'UPDATE '. $this->getStoreTableName() .' SET
                    preparation_status = ' . self::INDEX_STATUS_PREPARATION_STATUS_DONE . ",
                    preparation_error = '',
                    trigger_info = ?,
                    in_preparation_queue = 1 WHERE tenant = ?";
        $this->db->executeQuery($query, [
            sprintf('Reset preparation queue in "%s".', $className),
            $this->name,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndexingQueue(): void
    {
        Logger::info('Index-Actions - Resetting index queue');
        $className = (new \ReflectionClass($this))->getShortName();
        $query = 'UPDATE '. $this->getStoreTableName() .' SET
                    trigger_info = ?,
                    crc_index = 0 WHERE tenant = ?';
        $this->db->executeQuery($query, [
            sprintf('Reset indexing queue in "%s".', $className),
            $this->name,
        ]);
    }

    /**
     * @throws \Exception
     */
    protected function executeTransactionalQuery(\Closure $fn, int $maxTries = 3, float $sleep = .5): bool
    {
        for ($i = 1; $i <= $maxTries; $i++) {
            $this->db->beginTransaction();

            try {
                $fn();

                return $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
                Logger::warning("Executing transational query, no. {$i} of {$maxTries} tries failed. " . $e->getMessage());
                if ($i === $maxTries) {
                    throw $e;
                }
                usleep((int) ($sleep * 1000000));
            }
        }

        return false;
    }
}
