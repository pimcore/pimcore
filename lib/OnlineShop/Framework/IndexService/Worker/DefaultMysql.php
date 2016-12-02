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


namespace OnlineShop\Framework\IndexService\Worker;

use Pimcore\Cache;

class DefaultMysql extends AbstractWorker implements IWorker {
    protected $_sqlChangeLog = array();

    /**
     * @var \OnlineShop\Framework\IndexService\Config\IMysqlConfig
     */
    protected $tenantConfig;

    public function __construct(\OnlineShop\Framework\IndexService\Config\IMysqlConfig $tenantConfig) {
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
        $columnConfig = $this->columnConfig;
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
                        if($interpreterObject instanceof \OnlineShop\Framework\IndexService\Interpreter\IRelationInterpreter) {
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
            } catch(\Exception $e) {
                \Logger::info($e);
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

    public function deleteFromIndex(\OnlineShop\Framework\Model\IIndexable $object){
        if(!$this->tenantConfig->isActive($object)) {
            \Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach($subObjectIds as $subObjectId => $object) {
            $this->doDeleteFromIndex($subObjectId, $object);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);

    }

    protected function doDeleteFromIndex($subObjectId, \OnlineShop\Framework\Model\IIndexable $object = null) {
        $this->db->delete($this->tenantConfig->getTablename(), "o_id = " . $this->db->quote($subObjectId));
        $this->db->delete($this->tenantConfig->getRelationTablename(), "src = " . $this->db->quote($subObjectId));
        if($this->tenantConfig->getTenantRelationTablename()) {
            $this->db->delete($this->tenantConfig->getTenantRelationTablename(), "o_id = " . $this->db->quote($subObjectId));
        }
    }

    public function updateIndex(\OnlineShop\Framework\Model\IIndexable $object) {
        if(!$this->tenantConfig->isActive($object)) {
            \Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach($subObjectIds as $subObjectId => $object) {

            if($object->getOSDoIndexProduct() && $this->tenantConfig->inIndex($object)) {
                $a = \Pimcore::inAdmin();
                $b = \Pimcore\Model\Object\AbstractObject::doGetInheritedValues();
                \Pimcore::unsetAdminMode();
                \Pimcore\Model\Object\AbstractObject::setGetInheritedValues(true);
                $hidePublishedMemory = \Pimcore\Model\Object\AbstractObject::doHideUnpublished();
                \Pimcore\Model\Object\AbstractObject::setHideUnpublished(false);
                $categories = $this->tenantConfig->getCategories($object);
                $categoryIds = array();
                $parentCategoryIds = array();
                if($categories) {
                    foreach($categories as $c) {

                        if($c instanceof \OnlineShop\Framework\Model\AbstractCategory) {
                            $categoryIds[$c->getId()] = $c->getId();
                        }

                        $currentCategory = $c;
                        while($currentCategory instanceof \OnlineShop\Framework\Model\AbstractCategory) {
                            $parentCategoryIds[$currentCategory->getId()] = $currentCategory->getId();

                            if($currentCategory->getOSProductsInParentCategoryVisible()) {
                                $currentCategory = $currentCategory->getParent();
                            } else {
                                $currentCategory = null;
                            }
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
                            if($interpreterObject instanceof \OnlineShop\Framework\IndexService\Interpreter\IRelationInterpreter) {
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
                            $data[$column->name] = $this->convertArray($data[$column->name]);
                        }

                    } catch(\Exception $e) {
                        \Logger::err("Exception in IndexService: " . $e->getMessage(), $e);
                    }

                }
                if($a) {
                    \Pimcore::setAdminMode();
                }
                \Pimcore\Model\Object\AbstractObject::setGetInheritedValues($b);
                \Pimcore\Model\Object\AbstractObject::setHideUnpublished($hidePublishedMemory);

                try {

                    $this->doInsertData($data);

                } catch (\Exception $e) {
                    \Logger::warn("Error during updating index table: " . $e);
                }

                try {
                    $this->db->delete($this->tenantConfig->getRelationTablename(), "src = " . $this->db->quote($subObjectId));
                    foreach($relationData as $rd) {
                        $this->db->insert($this->tenantConfig->getRelationTablename(), $rd);
                    }
                } catch (\Exception $e) {
                    \Logger::warn("Error during updating index relation table: " . $e->getMessage(), $e);
                }
            } else {

                \Logger::info("Don't adding product " . $subObjectId . " to index.");

                try {
                    $this->db->delete($this->tenantConfig->getTablename(), "o_id = " . $this->db->quote($subObjectId));
                } catch (\Exception $e) {
                    \Logger::warn("Error during updating index table: " . $e->getMessage(), $e);
                }

                try {
                    $this->db->delete($this->tenantConfig->getRelationTablename(), "src = " . $this->db->quote($subObjectId));
                } catch (\Exception $e) {
                    \Logger::warn("Error during updating index relation table: " . $e->getMessage(), $e);
                }

                try {
                    if($this->tenantConfig->getTenantRelationTablename()) {
                        $this->db->delete($this->tenantConfig->getTenantRelationTablename(), "o_id = " . $this->db->quote($subObjectId));
                    }
                } catch (\Exception $e) {
                    \Logger::warn("Error during updating index tenant relation table: " . $e->getMessage(), $e);
                }

            }
            $subTenantData = $this->tenantConfig->prepareSubTenantEntries($object, $subObjectId);
            $this->tenantConfig->updateSubTenantEntries($object, $subTenantData, $subObjectId);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);
    }

    protected function doInsertData($data) {

        $validColumns = self::getValidTableColumns($this->tenantConfig->getTablename());
        foreach($data as $column => $value) {
            if(!in_array($column, $validColumns)) {
                unset($data[$column]);
            }
        }

        $this->db->insertOrUpdate($this->tenantConfig->getTablename(), $data);
    }

    protected function getValidTableColumns($table)
    {
        $cacheKey = "plugin_ecommerce_productindex_columns_" . $table;

        if (!$columns = Cache\Runtime::load($cacheKey)) {

            $columns = array();
            $data = $this->db->fetchAll("SHOW COLUMNS FROM " . $table);
            foreach ($data as $d) {
                $columns[] = $d["Field"];
            }

            Cache\Runtime::save($columns, $cacheKey);
        }

        return $columns;
    }

    protected function getSystemAttributes() {
        return array("o_id", "o_classId", "o_parentId", "o_virtualProductId", "o_virtualProductActive", "o_type", "categoryIds", "parentCategoryIds", "priceSystemName", "active", "inProductList");
    }

    protected function dbexec($sql) {
        $this->db->query($sql);
        $this->logSql($sql);
    }

    protected function logSql ($sql) {
        \Logger::info($sql);

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
        return new \OnlineShop\Framework\IndexService\ProductList\DefaultMysql($this->getTenantConfig());
    }
}

