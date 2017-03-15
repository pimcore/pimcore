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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker\Helper;

use Pimcore\Logger;
use Pimcore\Cache;

class MySql {

    protected $_sqlChangeLog = [];

    /**
     * @var \OnlineShop\Framework\IndexService\Config\IMysqlConfig
     */
    protected $tenantConfig;

    protected $db;

    public function __construct(\OnlineShop\Framework\IndexService\Config\IMysqlConfig $tenantConfig) {
        $this->tenantConfig = $tenantConfig;

        $this->db = \Pimcore\Db::get();

    }

    public function getValidTableColumns($table)
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

    public function doInsertData($data) {

        $validColumns = $this->getValidTableColumns($this->tenantConfig->getTablename());
        foreach($data as $column => $value) {
            if(!in_array($column, $validColumns)) {
                unset($data[$column]);
            }
        }

        $this->db->insertOrUpdate($this->tenantConfig->getTablename(), $data);
    }


    public function getSystemAttributes() {
        return array("o_id", "o_classId", "o_parentId", "o_virtualProductId", "o_virtualProductActive", "o_type", "categoryIds", "parentCategoryIds", "priceSystemName", "active", "inProductList");
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
        $columns = [];
        foreach ($data as $d) {
            $columns[$d["Field"]] = $d["Field"];
        }

        $systemColumns = $this->getSystemAttributes();

        $columnsToDelete = $columns;
        $columnsToAdd = array();
        $columnConfig = $this->tenantConfig->getAttributeConfig();
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

        $searchIndexColums = $this->tenantConfig->getSearchAttributeConfig();
        if(!empty($searchIndexColums)) {

            try {
                $this->dbexec('ALTER TABLE ' . $this->tenantConfig->getTablename() . ' DROP INDEX search;');
            } catch(\Exception $e) {
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

}