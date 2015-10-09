<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * provides worker functionality for patch preparing data and updating index
 *
 * Class OnlineShop_Framework_IndexService_Tenant_Worker_Traits_BatchProcessing
 */
trait OnlineShop_Framework_IndexService_Tenant_Worker_Traits_BatchProcessing {

    /**
     * creates store table
     */
    protected function createOrUpdateStoreTable() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $this->getStoreTableName() . "` (
          `id` bigint(20) NOT NULL DEFAULT '0',
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
          PRIMARY KEY (`id`,`tenant`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /**
     * deletes element from store table
     *
     * @param $objectId
     */
    protected function deleteFromStoreTable($objectId) {
        $this->db->delete($this->getStoreTableName(), "id = " . $this->db->quote((string)$objectId) . " AND tenant = " . $this->db->quote($this->name));
    }


    /**
     * prepare data for index creation and store is in store table
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     */
    public function prepareDataForIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach($subObjectIds as $subObjectId => $object) {
            /**
             * @var OnlineShop_Framework_ProductInterfaces_IIndexable $object
             */
            if($object->getOSDoIndexProduct() && $this->tenantConfig->inIndex($object)) {
                $a = Pimcore::inAdmin();
                $b = \Pimcore\Model\Object\AbstractObject::doGetInheritedValues();
                Pimcore::unsetAdminMode();
                \Pimcore\Model\Object\AbstractObject::setGetInheritedValues(true);
                $hidePublishedMemory = \Pimcore\Model\Object\AbstractObject::doHideUnpublished();
                \Pimcore\Model\Object\AbstractObject::setHideUnpublished(false);
                $categories = $object->getCategories();
                $categoryIds = array();
                $parentCategoryIds = array();
                if($categories) {
                    foreach($categories as $c) {
                        $parent = $c;

                        if ($parent != null) {
                            if($parent->getOSProductsInParentCategoryVisible()) {
                                while($parent && $parent instanceof OnlineShop_Framework_AbstractCategory) {
                                    $parentCategoryIds[$parent->getId()] = $parent->getId();
                                    $parent = $parent->getParent();
                                }
                            } else {
                                $parentCategoryIds[$parent->getId()] = $parent->getId();
                            }

                            $categoryIds[$c->getId()] = $c->getId();
                        }
                    }
                }

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
                    "priceSystemName" => $object->getPriceSystemName(),
                    "active" => $object->isActive(),
                    "inProductList" => $object->isActive(true)
                );

                $relationData = array();

                $columnConfig = $this->columnConfig->column;
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
                            if($interpreterObject instanceof OnlineShop_Framework_IndexService_RelationInterpreter) {
                                foreach($value as $v) {
                                    $relData = array();
                                    $relData['src'] = $subObjectId;
                                    $relData['src_virtualProductId'] = $virtualProductId;
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
                            $data[$column->name] = OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER . implode($data[$column->name], OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER) . OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER;
                        }

                    } catch(Exception $e) {
                        Logger::err("Exception in IndexService: " . $e->getMessage(), $e);
                    }

                }
                if($a) {
                    Pimcore::setAdminMode();
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
                    "id" => $subObjectId,
                    "tenant" => $this->name,
                    "data" => $jsonData,
                    "crc_current" => $crc,
                    "preparation_worker_timestamp" => 0,
                    "preparation_worker_id" => $this->db->quote(null),
                    "in_preparation_queue" => 0
                );

                $currentEntry = $this->db->fetchRow("SELECT crc_current, in_preparation_queue FROM " . $this->getStoreTableName() . " WHERE id = ? AND tenant = ?", array($subObjectId, $this->name));
                if(!$currentEntry) {
                    $this->db->insert($this->getStoreTableName(), $insertData);
                } else if($currentEntry['crc_current'] != $crc) {
                    $this->db->update($this->getStoreTableName(), $insertData, "id = " . $this->db->quote((string)$subObjectId) . " AND tenant = " . $this->db->quote($this->name));
                } else if($currentEntry['in_preparation_queue']) {
                    $this->db->query("UPDATE " . $this->getStoreTableName() . " SET in_preparation_queue = 0, preparation_worker_timestamp = 0, preparation_worker_id = null WHERE id = ? AND tenant = ?", array($subObjectId, $this->name));
                }

            } else {
                Logger::info("Don't adding product " . $subObjectId . " to index " . $this->name . ".");
                $this->doDeleteFromIndex($subObjectId);
            }
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);

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
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     */
    public function fillupPreparationQueue(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        if($object instanceof \Pimcore\Model\Object\Concrete) {

            //need check, if there are sub objects because update on empty result set is too slow
            $objects = $this->db->fetchCol("SELECT o_id FROM objects WHERE o_path LIKE ?", array($object->getFullPath() . "/%"));
            if($objects) {
                $updateStatement =
                    "UPDATE " . $this->getStoreTableName() . " SET in_preparation_queue = 1 WHERE tenant = ? AND id IN
                 (SELECT o_id FROM objects WHERE o_path LIKE ?)";

                $this->db->query($updateStatement, array($this->name, $object->getFullPath() . "/%"));
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
        $workerTimestamp = Zend_Date::now()->getTimestamp();
        $this->db->query("UPDATE " . $this->getStoreTableName() . " SET preparation_worker_id = ?, preparation_worker_timestamp = ? WHERE tenant = ? AND in_preparation_queue = 1 AND (ISNULL(preparation_worker_timestamp) OR preparation_worker_timestamp < ?) LIMIT " . intval($limit),
            array($workerId, $workerTimestamp, $this->name, $workerTimestamp - $this->getWorkerTimeout()));

        $entries = $this->db->fetchCol("SELECT id FROM " . $this->getStoreTableName() . " WHERE preparation_worker_id = ?",
            array($workerId));

        if($entries) {
            foreach($entries as $objectId) {
                Logger::info("Worker $workerId preparing data for index for element " . $objectId);

                $object = $this->tenantConfig->getObjectById($objectId, true);
                if($object instanceof OnlineShop_Framework_ProductInterfaces_IIndexable) {
                    $this->prepareDataForIndex($object);
                } else {
                    //delete entry with id which was retrieved from index before
                    Logger::warn("Element with ID $objectId in product index but cannot be found in pimcore -> deleting element from index.");
                    $this->doDeleteFromIndex($objectId);
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
        $workerTimestamp = Zend_Date::now()->getTimestamp();
        $this->db->query("UPDATE " . $this->getStoreTableName() . " SET worker_id = ?, worker_timestamp = ? WHERE (crc_current != crc_index OR ISNULL(crc_index)) AND tenant = ? AND (ISNULL(worker_timestamp) OR worker_timestamp < ?) LIMIT " . intval($limit),
            array($workerId, $workerTimestamp, $this->name, $workerTimestamp - $this->getWorkerTimeout()));

        $entries = $this->db->fetchAll("SELECT id, data FROM " . $this->getStoreTableName() . " WHERE worker_id = ?", array($workerId));

        if($entries) {
            foreach($entries as $entry) {
                Logger::info("Worker $workerId updating index for element " . $entry['id']);
                $data = json_decode($entry['data'], true);
                $this->doUpdateIndex($entry['id'], $data);
            }
            return count($entries);
        } else {
            return 0;
        }
    }




}