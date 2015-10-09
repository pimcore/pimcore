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


class OnlineShop_Framework_IndexService_Tenant_Worker_DefaultMysql extends OnlineShop_Framework_IndexService_Tenant_Worker_Abstract implements OnlineShop_Framework_IndexService_Tenant_IWorker {
    protected $_sqlChangeLog = array();

    /**
     * @var OnlineShop_Framework_IndexService_Tenant_IMysqlConfig
     */
    protected $tenantConfig;

    public function __construct(OnlineShop_Framework_IndexService_Tenant_IMysqlConfig $tenantConfig) {
        parent::__construct($tenantConfig);
    }


    public function createOrUpdateIndexStructures() {
        $primaryIdColumnType = $this->tenantConfig->getIdColumnType(true);
        $idColumnType = $this->tenantConfig->getIdColumnType(false);

        $this->dbexec("CREATE TABLE IF NOT EXISTS `" . $this->tenantConfig->getTablename() . "` (
          `o_id` $primaryIdColumnType,
          `o_virtualProductId` $idColumnType,
          `o_virtualProductActive` TINYINT(1) NOT NULL,
          `o_classId` int(11) NOT NULL,
          `o_parentId` $idColumnType,
          `o_type` varchar(20) NOT NULL,
          `categoryIds` varchar(255) NOT NULL,
          `parentCategoryIds` varchar(255) NOT NULL,
          `priceSystemName` varchar(50) NOT NULL,
          `active` TINYINT(1) NOT NULL,
          `inProductList` TINYINT(1) NOT NULL,
          PRIMARY KEY  (`o_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $data = $this->db->fetchAll("SHOW COLUMNS FROM " . $this->tenantConfig->getTablename());
        foreach ($data as $d) {
            $columns[$d["Field"]] = $d["Field"];
        }

        $systemColumns = $this->getSystemAttributes();

        $columnsToDelete = $columns;
        $columnsToAdd = array();
        $columnConfig = $this->columnConfig->column;
        if(!empty($columnConfig->name)) {
            $columnConfig = array($columnConfig);
        }
        if($columnConfig) {
            foreach($columnConfig as $column) {
                if(!array_key_exists($column->name, $columns)) {

                    $doAdd = true;
                    if(!empty($column->interpreter)) {
                        $interpreter = $column->interpreter;
                        $interpreterObject = new $interpreter();
                        if($interpreterObject instanceof OnlineShop_Framework_IndexService_RelationInterpreter) {
                            $doAdd = false;
                        }
                    }

                    if($doAdd) {
                        $columnsToAdd[$column->name] = $column->type;
                    }
                }
                unset($columnsToDelete[$column->name]);
            }
        }
        foreach($columnsToDelete as $c) {
            if(!in_array($c, $systemColumns)) {
                $this->dbexec('ALTER TABLE `' . $this->tenantConfig->getTablename() . '` DROP COLUMN `' . $c . '`;');
            }
        }


        foreach($columnsToAdd as $c => $type) {
            $this->dbexec('ALTER TABLE `' . $this->tenantConfig->getTablename() . '` ADD `' . $c . '` ' . $type . ';');
        }

        $searchIndexColums = $this->getGeneralSearchAttributes();
        if(!empty($searchIndexColums)) {

            try {
                $this->dbexec('ALTER TABLE ' . $this->tenantConfig->getTablename() . ' DROP INDEX search;');
            } catch(Exception $e) {
                Logger::info($e);
            }

            $this->dbexec('ALTER TABLE `' . $this->tenantConfig->getTablename() . '` ENGINE = MyISAM;');
            $columnNames = array();
            foreach($searchIndexColums as $c) {
                $columnNames[] = $this->db->quoteIdentifier($c);
            }
            $this->dbexec('ALTER TABLE `' . $this->tenantConfig->getTablename() . '` ADD FULLTEXT INDEX search (' . implode(",", $columnNames) . ');');
        }


        $this->dbexec("CREATE TABLE IF NOT EXISTS `" . $this->tenantConfig->getRelationTablename() . "` (
          `src` $idColumnType,
          `src_virtualProductId` int(11) NOT NULL,
          `dest` int(11) NOT NULL,
          `fieldname` varchar(255) COLLATE utf8_bin NOT NULL,
          `type` varchar(20) COLLATE utf8_bin NOT NULL,
          PRIMARY KEY (`src`,`dest`,`fieldname`,`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");

        if($this->tenantConfig->getTenantRelationTablename()) {
            $this->dbexec("CREATE TABLE IF NOT EXISTS `" . $this->tenantConfig->getTenantRelationTablename() . "` (
              `o_id` $idColumnType,
              `subtenant_id` int(11) NOT NULL,
              PRIMARY KEY (`o_id`,`subtenant_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");
        }

    }

    public function deleteFromIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object){
        if(!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach($subObjectIds as $subObjectId => $object) {
            $this->doDeleteFromIndex($subObjectId);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);

    }

    protected function doDeleteFromIndex($subObjectId) {
        $this->db->delete($this->tenantConfig->getTablename(), "o_id = " . $this->db->quote($subObjectId));
        $this->db->delete($this->tenantConfig->getRelationTablename(), "src = " . $this->db->quote($subObjectId));
        if($this->tenantConfig->getTenantRelationTablename()) {
            $this->db->delete($this->tenantConfig->getTenantRelationTablename(), "o_id = " . $this->db->quote($subObjectId));
        }
    }

    public function updateIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        if(!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach($subObjectIds as $subObjectId => $object) {

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

                try {

                    $this->doInsertData($data);

                } catch (Exception $e) {
                    Logger::warn("Error during updating index table: " . $e);
                }

                try {
                    $this->db->delete($this->tenantConfig->getRelationTablename(), "src = " . $this->db->quote($subObjectId));
                    foreach($relationData as $rd) {
                        $this->db->insert($this->tenantConfig->getRelationTablename(), $rd);
                    }
                } catch (Exception $e) {
                    Logger::warn("Error during updating index relation table: " . $e->getMessage(), $e);
                }
            } else {

                Logger::info("Don't adding product " . $subObjectId . " to index.");

                try {
                    $this->db->delete($this->tenantConfig->getTablename(), "o_id = " . $this->db->quote($subObjectId));
                } catch (Exception $e) {
                    Logger::warn("Error during updating index table: " . $e->getMessage(), $e);
                }

                try {
                    $this->db->delete($this->tenantConfig->getRelationTablename(), "src = " . $this->db->quote($subObjectId));
                } catch (Exception $e) {
                    Logger::warn("Error during updating index relation table: " . $e->getMessage(), $e);
                }

                try {
                    if($this->tenantConfig->getTenantRelationTablename()) {
                        $this->db->delete($this->tenantConfig->getTenantRelationTablename(), "o_id = " . $this->db->quote($subObjectId));
                    }
                } catch (Exception $e) {
                    Logger::warn("Error during updating index tenant relation table: " . $e->getMessage(), $e);
                }

            }
            $this->tenantConfig->updateSubTenantEntries($object, $subObjectId);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);
    }

    protected function doInsertData($data) {
        //insert index data
        $dataKeys = [];
        $updateData = [];
        $insertData = [];
        $insertStatement = [];

        foreach($data as $key => $d) {
            $dataKeys[$this->db->quoteIdentifier($key)] = '?';
            $updateData[] = $d;
            $insertStatement[] = $this->db->quoteIdentifier($key) . " = ?";
            $insertData[] = $d;
        }

        $insert = "INSERT INTO " . $this->tenantConfig->getTablename() . " (" . implode(",", array_keys($dataKeys)) . ") VALUES (" . implode("," , $dataKeys) . ")"
            . " ON DUPLICATE KEY UPDATE " . implode(",", $insertStatement);

        $this->db->query($insert, array_merge($updateData, $insertData));
    }

    protected function getSystemAttributes() {
        return array("o_id", "o_classId", "o_parentId", "o_virtualProductId", "o_virtualProductActive", "o_type", "categoryIds", "parentCategoryIds", "priceSystemName", "active", "inProductList");
    }

    protected function dbexec($sql) {
        $this->db->query($sql);
        $this->logSql($sql);
    }

    protected function logSql ($sql) {
        Logger::info($sql);

        $this->_sqlChangeLog[] = $sql;
    }

    public function __destruct () {

        // write sql change log for deploying to production system
        if(!empty($this->_sqlChangeLog)) {
            $log = implode("\n\n\n", $this->_sqlChangeLog);

            $filename = "db-change-log_".time()."_productindex.sql";
            $file = PIMCORE_SYSTEM_TEMP_DIRECTORY."/".$filename;
            if(defined("PIMCORE_DB_CHANGELOG_DIRECTORY")) {
                $file = PIMCORE_DB_CHANGELOG_DIRECTORY."/".$filename;
            }

            file_put_contents($file, $log);
        }
    }

    /**
     * returns product list implementation valid and configured for this worker/tenant
     *
     * @return mixed
     */
    function getProductList() {
        return new OnlineShop_Framework_ProductList_DefaultMysql($this->getTenantConfig());
    }
}

