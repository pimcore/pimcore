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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Interpreter\IRelationInterpreter;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Logger;


/**
 * Class AbstractMockupCacheWorker
 *
 * provides worker functionality for patch preparing data and updating index
 *
 * @package OnlineShop\Framework\IndexService\Worker
 */
abstract class AbstractBatchProcessingWorker extends AbstractWorker implements IBatchProcessingWorker {

    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\AbstractConfig
     */
    protected $tenantConfig;

    /**
     * returns name for store table
     *
     * @return string
     */
    protected abstract function getStoreTableName();


    /**
     * @param $objectId
     * @param null $data
     */
    protected abstract function doUpdateIndex($objectId, $data = null);


    /**
     * creates store table
     */
    protected function createOrUpdateStoreTable() {
        $primaryIdColumnType = $this->tenantConfig->getIdColumnType(true);
        $idColumnType = $this->tenantConfig->getIdColumnType(false);

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $this->getStoreTableName() . "` (
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
    protected function deleteFromStoreTable($objectId) {
        $this->db->deleteWhere($this->getStoreTableName(), "o_id = " . $this->db->quote((string)$objectId) . " AND tenant = " . $this->db->quote($this->name));
    }

    /**
     * prepare data for index creation and store is in store table
     *
     * @param IIndexable $object
     */
    protected function getDefaultDataForIndex(IIndexable $object, $subObjectId){
        $categories = $object->getCategories();
        $categoryIds = [];
        $parentCategoryIds =[];
        $categoryIdPaths = [];
        if($categories) {
            foreach($categories as $c) {

                if($c instanceof AbstractCategory) {
                    $categoryIds[$c->getId()] = $c->getId();
                }

                $currentCategory = $c;
                while($currentCategory instanceof AbstractCategory) {
                    $parentCategoryIds[$currentCategory->getId()] = $currentCategory->getId();

                    if($currentCategory->getOSProductsInParentCategoryVisible()) {
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
                foreach($tmpIds as $id){
                    $s.= '/'.$id;
                    $categoryIdPaths[] = $s;
                }


            }
        }
        $categoryIdPaths = (array)array_unique($categoryIdPaths);
        sort($categoryIdPaths);
        ksort($categoryIds);

        $virtualProductId = $subObjectId;
        $virtualProductActive = $object->isActive();
        if($object->getOSIndexType() == "variant") {
            $virtualProductId = $this->tenantConfig->createVirtualParentIdForSubId($object, $subObjectId);
        }

        $virtualProduct = \Pimcore\Model\Object\AbstractObject::getById($virtualProductId);
        if($virtualProduct && method_exists($virtualProduct, "isActive")) {
            $virtualProductActive = $virtualProduct->isActive();
        }

        $data = array(
            "o_id" => $subObjectId,
            "o_classId" => $object->getClassId(),
            "o_virtualProductId" => $virtualProductId,
            "o_virtualProductActive" => $virtualProductActive,
            "o_parentId" => $object->getOSParentId(),
            "o_type" => $object->getOSIndexType(),
            "categoryIds" => ',' . implode(",", $categoryIds) . ",",
            "parentCategoryIds" => ',' . implode(",", $parentCategoryIds) . ",",
            "categoryPaths" => (array)$categoryIdPaths,
            "priceSystemName" => $object->getPriceSystemName(),
            "active" => $object->isActive(),
            "inProductList" => $object->isActive(true),
            "tenant" => $this->name,
        );


        return $data;
    }

    /**
     * prepare data for index creation and store is in store table
     *
     * @param IIndexable $object
     */
    public function prepareDataForIndex(IIndexable $object) {
        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach($subObjectIds as $subObjectId => $object) {
            /**
             * @var IIndexable $object
             */
            if($object->getOSDoIndexProduct() && $this->tenantConfig->inIndex($object)) {
                $a = \Pimcore::inAdmin();
                $b = \Pimcore\Model\Object\AbstractObject::doGetInheritedValues();
                \Pimcore::unsetAdminMode();
                \Pimcore\Model\Object\AbstractObject::setGetInheritedValues(true);
                $hidePublishedMemory = \Pimcore\Model\Object\AbstractObject::doHideUnpublished();
                \Pimcore\Model\Object\AbstractObject::setHideUnpublished(false);

                $data = $this->getDefaultDataForIndex($object,$subObjectId);
                $relationData = array();

                $columnConfig = $this->columnConfig;
                if(!empty($columnConfig->name)) {
                    $columnConfig = array($columnConfig);
                }
                else if(empty($columnConfig))
                {
                    $columnConfig = array();
                }
                foreach($columnConfig as $column) {
                    try {
                        //$data[$column->name] = null;
                        $value = null;
                        if(!empty($column->getter)) {
                            $getter = $column->getter;
                            $value = $getter::get($object, $column->config, $subObjectId, $this->tenantConfig);
                        } else {
                            if(!empty($column->fieldname)) {
                                $getter = "get" . ucfirst($column->fieldname);
                            } else {
                                $getter = "get" . ucfirst($column->name);
                            }

                            if(method_exists($object, $getter)) {
                                $value = $object->$getter($column->locale);
                            }
                        }

                        if(!empty($column->interpreter)) {
                            $interpreter = $column->interpreter;
                            $value = $interpreter::interpret($value, $column->config);
                            $interpreterObject = new $interpreter();
                            if($interpreterObject instanceof IRelationInterpreter) {
                                foreach($value as $v) {
                                    $relData = array();
                                    $relData['src'] = $subObjectId;
                                    $relData['src_virtualProductId'] = $data['o_virtualProductId'];
                                    $relData['dest'] = $v['dest'];
                                    $relData['fieldname'] = $column->name;
                                    $relData['type'] = $v['type'];
                                    $relationData[] = $relData;
                                }
                            } else {
                                $data[$column->name] = $value;
                            }
                        } else {
                            $data[$column->name] = $value;
                        }

                        if(is_array($data[$column->name])) {
                            $data[$column->name] = $this->convertArray($data[$column->name]);
                        }

                    } catch(\Exception $e) {
                        Logger::err("Exception in IndexService: " . $e->getMessage(), $e);
                    }

                }
                if($a) {
                    \Pimcore::setAdminMode();
                }
                \Pimcore\Model\Object\AbstractObject::setGetInheritedValues($b);
                \Pimcore\Model\Object\AbstractObject::setHideUnpublished($hidePublishedMemory);


                $subTenantData = $this->tenantConfig->prepareSubTenantEntries($object, $subObjectId);
                $jsonData = json_encode(array(
                    "data" => $data,
                    "relations" => ($relationData ? $relationData : []),
                    "subtenants" => ($subTenantData ? $subTenantData : [])
                ));

                $crc = crc32($jsonData);
                $insertData = array(
                    "o_id" => $subObjectId,
                    "o_virtualProductId" => $data['o_virtualProductId'],
                    "tenant" => $this->name,
                    "data" => $jsonData,
                    "crc_current" => $crc,
                    "preparation_worker_timestamp" => 0,
                    "preparation_worker_id" => $this->db->quote(null),
                    "in_preparation_queue" => 0
                );

                $this->insertDataToIndex($insertData,$subObjectId);
            } else {
                Logger::info("Don't adding product " . $subObjectId . " to index " . $this->name . ".");
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
    protected function insertDataToIndex($data,$subObjectId){
        $currentEntry = $this->db->fetchRow("SELECT crc_current, in_preparation_queue FROM " . $this->getStoreTableName() . " WHERE o_id = ? AND tenant = ?", array($subObjectId, $this->name));
        if(!$currentEntry) {
            $this->db->insert($this->getStoreTableName(), $data);
        } else if($currentEntry['crc_current'] != $data['crc_current']) {
            $this->db->updateWhere($this->getStoreTableName(), $data, "o_id = " . $this->db->quote((string)$subObjectId) . " AND tenant = " . $this->db->quote($this->name));
        } else if($currentEntry['in_preparation_queue']) {
            $this->db->query("UPDATE " . $this->getStoreTableName() . " SET in_preparation_queue = 0, preparation_worker_timestamp = 0, preparation_worker_id = null WHERE o_id = ? AND tenant = ?", array($subObjectId, $this->name));
        }
    }


    protected function getWorkerTimeout() {
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
    public function fillupPreparationQueue(IIndexable $object) {
        if($object instanceof \Pimcore\Model\Object\Concrete) {

            //need check, if there are sub objects because update on empty result set is too slow
            $objects = $this->db->fetchCol("SELECT o_id FROM objects WHERE o_path LIKE ?", array($object->getFullPath() . "/%"));
            if($objects) {
                $updateStatement = "UPDATE " . $this->getStoreTableName() . " SET in_preparation_queue = 1 WHERE tenant = ? AND o_id IN (".implode(',',$objects).")";
                $this->db->query($updateStatement, array($this->name));
            }

        }
    }

    /**
     * processes elements in the queue for preparation of index data
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     * @return int number of entries
     */
    public function processPreparationQueue($limit = 200) {

        $workerId = uniqid();
        $workerTimestamp = time();
        $this->db->query("UPDATE " . $this->getStoreTableName() . " SET preparation_worker_id = ?, preparation_worker_timestamp = ? WHERE tenant = ? AND in_preparation_queue = 1 AND (ISNULL(preparation_worker_timestamp) OR preparation_worker_timestamp < ?) LIMIT " . intval($limit),
            array($workerId, $workerTimestamp, $this->name, $workerTimestamp - $this->getWorkerTimeout()));

        $entries = $this->db->fetchCol("SELECT o_id FROM " . $this->getStoreTableName() . " WHERE preparation_worker_id = ?",
            array($workerId));

        if($entries) {
            foreach($entries as $objectId) {
                Logger::info("Worker $workerId preparing data for index for element " . $objectId);

                $object = $this->tenantConfig->getObjectById($objectId, true);
                if($object instanceof IIndexable) {
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
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200) {

        $workerId = uniqid();
        $workerTimestamp = time();
        $this->db->query("UPDATE " . $this->getStoreTableName() . " SET worker_id = ?, worker_timestamp = ? WHERE (crc_current != crc_index OR ISNULL(crc_index)) AND tenant = ? AND (ISNULL(worker_timestamp) OR worker_timestamp < ?) LIMIT " . intval($limit),
            array($workerId, $workerTimestamp, $this->name, $workerTimestamp - $this->getWorkerTimeout()));

        $entries = $this->db->fetchAll("SELECT o_id, data FROM " . $this->getStoreTableName() . " WHERE worker_id = ?", array($workerId));

        if($entries) {
            foreach($entries as $entry) {
                Logger::info("Worker $workerId updating index for element " . $entry['id']);
                $data = json_decode($entry['data'], true);
                $this->doUpdateIndex($entry['o_id'], $data);

            }
            return count($entries);
        } else {
            return 0;
        }
    }




}