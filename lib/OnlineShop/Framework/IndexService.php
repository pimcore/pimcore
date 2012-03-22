<?php

class OnlineShop_Framework_IndexService {
    const TABLENAME = "plugin_onlineshop_productindex";
    const RELATIONTABLENAME = "plugin_onlineshop_productindex_relations";

    const MULTISELECT_DELIMITER = "#;#";

    private $_sqlChangeLog = array();
    private $columnConfig;
    private $searchColumnConfig;

    public function __construct($config) {
        $this->columnConfig = $config->columns;

        $this->searchColumnConfig = array();
        if($config->generalSearchColumns->column) {
            foreach($config->generalSearchColumns->column as $c) {
                $this->searchColumnConfig[] = $c->name;
            }
        }


        $this->db = Pimcore_Resource::get();
        try {
//            $this->createOrUpdateTable();

        } catch(Exception $e) {
            throw $e;
        }
    }

    public function getGeneralSearchColumns() {
        return $this->searchColumnConfig;
    }

    public function createOrUpdateTable() {
        $this->dbexec("CREATE TABLE IF NOT EXISTS `" . self::TABLENAME . "` (
          `o_id` int(11) NOT NULL default '0',
          `o_virtualProductId` int(11) NOT NULL,
          `o_virtualProductActive` TINYINT(1) NOT NULL,
          `o_classId` int(11) NOT NULL,
          `o_parentId` int(11) NOT NULL,
          `o_type` varchar(20) NOT NULL,
          `categoryIds` varchar(255) NOT NULL,
          `parentCategoryIds` varchar(255) NOT NULL,
          `priceSystemName` varchar(50) NOT NULL,
          `active` TINYINT(1) NOT NULL,
          `inProductList` TINYINT(1) NOT NULL,
          PRIMARY KEY  (`o_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $data = $this->db->fetchAll("SHOW COLUMNS FROM " . self::TABLENAME);
        foreach ($data as $d) {
            $columns[$d["Field"]] = $d["Field"];
        }

        $systemColumns = $this->getSystemColumns();

        $columnsToDelete = $columns;
        $columnsToAdd = array();
        $columnConfig = $this->columnConfig->column;
        if(!empty($columnConfig->name)) {
            $columnConfig = array($columnConfig);
        }
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
        foreach($columnsToDelete as $c) {
            if(!in_array($c, $systemColumns)) {
                $this->dbexec('ALTER TABLE `' . self::TABLENAME . '` DROP COLUMN `' . $c . '`;');
            }
        }


        foreach($columnsToAdd as $c => $type) {
            $this->dbexec('ALTER TABLE `' . self::TABLENAME . '` ADD `' . $c . '` ' . $type . ';');
        }

        $searchIndexColums = $this->getGeneralSearchColumns();
        if(!empty($searchIndexColums)) {

            try {
                $this->dbexec('ALTER TABLE protouchng.plugin_onlineshop_productindex DROP INDEX search;');
            } catch(Exception $e) {
                Logger::info($e);
            }

            $this->dbexec('ALTER TABLE `' . self::TABLENAME . '` ENGINE = MyISAM;');
            $columnNames = array();
            foreach($searchIndexColums as $c) {
                $columnNames[] = $this->db->quoteIdentifier($c);
            }
            $this->dbexec('ALTER TABLE `' . self::TABLENAME . '` ADD FULLTEXT INDEX search (' . implode(",", $columnNames) . ')');
        }


        $this->dbexec("CREATE TABLE IF NOT EXISTS `" . self::RELATIONTABLENAME . "` (
          `src` int(11) NOT NULL,
          `src_virtualProductId` int(11) NOT NULL,
          `dest` int(11) NOT NULL,
          `fieldname` varchar(255) COLLATE utf8_bin NOT NULL,
          `type` varchar(20) COLLATE utf8_bin NOT NULL,
          PRIMARY KEY (`src`,`dest`,`fieldname`,`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");

    }


    public function deleteFromIndex(OnlineShop_Framework_AbstractProduct $object){
        $this->db->delete(self::TABLENAME,"o_id = " . $object->getId());
    }

    public function updateIndex(OnlineShop_Framework_AbstractProduct $object) {

        if($object->getOSDoIndexProduct()) {
            $a = Pimcore::inAdmin();
            $b = Object_Abstract::doGetInheritedValues();
            Pimcore::unsetAdminMode();
            Object_Abstract::setGetInheritedValues(true);
            $hidePublishedMemory = Object_Abstract::doHideUnpublished();
            Object_Abstract::setHideUnpublished(false);
            $categories = $object->getCategories();
            $categoryIds = array();
            $parentCategoryIds = array();
            if($categories) {
                foreach($categories as $c) {
                    $parent = $c;

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

            ksort($categoryIds);

            $virtualProductId = $object->getId();
            $virtualProductActive = $object->isActive();
            if($object->getOSIndexType() == "variant") {
                $virtualProductId = $object->getOSParentId();
            }

            $virtualProduct = Object_Abstract::getById($virtualProductId);
            if($virtualProduct && method_exists($virtualProduct, "isActive")) {
                $virtualProductActive = $virtualProduct->isActive();
            }

            $data = array(
                "o_id" => $object->getId(),
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
            foreach($columnConfig as $column) {
                try {
                    $value = null;
                    if(!empty($column->getter)) {
                        $getter = $column->getter;
                        $value = $getter::get($object, $column->config);
                    } else {
                        if(!empty($column->fieldname)) {
                            $getter = get . ucfirst($column->fieldname);
                        } else {
                            $getter = get . ucfirst($column->name);
                        }

                        if(method_exists($object, $getter)) {
                            $value = $object->$getter();
                        }
                    }

                    if(!empty($column->interpreter)) {
                        $interpreter = $column->interpreter;
                        $value = $interpreter::interpret($value, $column->config);
                        $interpreterObject = new $interpreter();
                        if($interpreterObject instanceof OnlineShop_Framework_IndexService_RelationInterpreter) {
                            foreach($value as $v) {
                                $relData = array();
                                $relData['src'] = $object->getId();
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
                        $data[$column->name] = self::MULTISELECT_DELIMITER . implode($data[$column->name], self::MULTISELECT_DELIMITER) . self::MULTISELECT_DELIMITER;
                    }

                } catch(Exception $e) {
                    Logger::err("Exception in IndexService: " . $e->getMessage(), $e);
                }

            }
            if($a) {
                Pimcore::setAdminMode();
            }
            Object_Abstract::setGetInheritedValues($b);
            Object_Abstract::setHideUnpublished($hidePublishedMemory);

            try {
                $this->db->update(self::TABLENAME, array("o_virtualProductActive" => $virtualProductActive), "o_virtualProductId = " . $virtualProductId);
                $this->db->insert(self::TABLENAME, $data);
            } catch (Exception $e) {
                try {
                    $this->db->update(self::TABLENAME, $data, "o_id = " . $object->getId());
                } catch (Exception $ex) {
                    Logger::warn("Error during updating index table: " . $ex->getMessage(), $ex);
                }
            }

            try {
                $this->db->delete(self::RELATIONTABLENAME, "src = " . $this->db->quote($object->getId()));
                foreach($relationData as $rd) {
                    $this->db->insert(self::RELATIONTABLENAME, $rd);
                }
            } catch (Exception $e) {
                Logger::warn("Error during updating index relation table: " . $e->getMessage(), $e);
            }



        } else {

            Logger::info("Don't adding product " . $object->getId() . " to index.");

            try {
                $this->db->delete(self::TABLENAME, "o_id = " . $this->db->quote($object->getId()));
            } catch (Exception $e) {
                Logger::warn("Error during updating index relation table: " . $e->getMessage(), $e);
            }

            try {
                $this->db->delete(self::RELATIONTABLENAME, "src = " . $this->db->quote($object->getId()));
            } catch (Exception $e) {
                Logger::warn("Error during updating index relation table: " . $e->getMessage(), $e);
            }

        }

    }

    public function getIndexAttributes($id) {
        return $this->db->fetchRow("SELECT * FROM " . self::TABLENAME . " WHERE o_id = " . $this->db->quote($id));
    }

    private function getSystemColumns() {
        return array("o_id", "o_classId", "o_parentId", "o_virtualProductId", "o_virtualProductActive", "o_type", "categoryIds", "parentCategoryIds", "priceSystemName", "active", "inProductList");
    }

    public function getIndexColumns($considerHideInFieldList = false) {
        if(empty($this->indexColumns)) {
            $this->indexColumns = array();

            $this->indexColumns["categoryIds"] = "categoryIds";

            foreach($this->columnConfig->column as $column) {
                if($considerHideInFieldList && $column->hideInFieldlistDatatype != "true") {
                    $this->indexColumns[$column->name] = $column->name;
                }
            }
            $this->indexColumns = array_values($this->indexColumns);
        }

        return $this->indexColumns;
    }
    

    private function getAllColumns() {
        return array_merge($this->getSystemColumns(), $this->getIndexColumns());
    }


    private function dbexec($sql) {
        $this->db->query($sql);
        $this->logSql($sql);
    }

    private function logSql ($sql) {
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
}

