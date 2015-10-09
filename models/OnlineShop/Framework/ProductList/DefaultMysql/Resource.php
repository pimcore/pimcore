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


class OnlineShop_Framework_ProductList_DefaultMysql_Resource {

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    private $db;

    /**
     * @var OnlineShop_Framework_IProductList
     */
    private $model;

    /**
     * @var int
     */
    private $lastRecordCount;


    public function __construct(OnlineShop_Framework_IProductList $model) {
        $this->model = $model;
        $this->db = \Pimcore\Resource::get();
    }


    public function load($condition, $orderBy = null, $limit = null, $offset = null) {

        if($condition) {
            $condition = "WHERE " . $condition;
        }

        if($orderBy) {
            $orderBy = " ORDER BY " . $orderBy;
        }

        if($limit) {
            if($offset) {
                $limit = "LIMIT " . $offset . ", " . $limit;
            } else {
                $limit = "LIMIT " . $limit;
            }
        }

        if($this->model->getVariantMode() == OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            if($orderBy) {
                $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT o_virtualProductId as o_id, priceSystemName FROM "
                    . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . " GROUP BY o_virtualProductId, priceSystemName" . $orderBy . " " . $limit;
            } else {
                $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT o_virtualProductId as o_id, priceSystemName FROM "
                    . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . " " . $limit;
            }
        } else {
            $query = "SELECT SQL_CALC_FOUND_ROWS a.o_id, priceSystemName FROM "
                . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . $orderBy . " " . $limit;
        }
        OnlineShop_Plugin::getSQLLogger()->log("Query: " . $query, Zend_Log::INFO);
        $result = $this->db->fetchAll($query);
        $this->lastRecordCount = (int)$this->db->fetchOne('SELECT FOUND_ROWS()');
        OnlineShop_Plugin::getSQLLogger()->log("Query done.", Zend_Log::INFO);
        return $result;
    }

    public function loadGroupByValues($fieldname, $condition, $countValues = false) {

        if($condition) {
            $condition = "WHERE " . $condition;
        }

        if($countValues) {
            if($this->model->getVariantMode() == OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                $query = "SELECT TRIM(`$fieldname`) as `value`, count(DISTINCT o_virtualProductId) as `count` FROM "
                    . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . " GROUP BY TRIM(`" . $fieldname . "`)";
            } else {
                $query = "SELECT TRIM(`$fieldname`) as `value`, count(*) as `count` FROM "
                    . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . " GROUP BY TRIM(`" . $fieldname . "`)";
            }

            OnlineShop_Plugin::getSQLLogger()->log("Query: " . $query, Zend_Log::INFO);
            $result = $this->db->fetchAll($query);
            OnlineShop_Plugin::getSQLLogger()->log("Query done.", Zend_Log::INFO);
            return $result;
        } else {
            $query = "SELECT " . $this->db->quoteIdentifier($fieldname) . " FROM "
                . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . " GROUP BY " . $this->db->quoteIdentifier($fieldname);
            OnlineShop_Plugin::getSQLLogger()->log("Query: " . $query, Zend_Log::INFO);
            $result = $this->db->fetchCol($query);
            OnlineShop_Plugin::getSQLLogger()->log("Query done.", Zend_Log::INFO);
            return $result;
        }
    }

    public function loadGroupByRelationValues($fieldname, $condition, $countValues = false) {

        if($condition) {
            $condition = "WHERE " . $condition;
        }

        if($countValues) {
            if($this->model->getVariantMode() == OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                $query = "SELECT dest as `value`, count(DISTINCT src_virtualProductId) as `count` FROM "
                    . $this->model->getCurrentTenantConfig()->getRelationTablename() . " a "
                    . "WHERE fieldname = " . $this->quote($fieldname);
            } else {
                $query = "SELECT dest as `value`, count(*) as `count` FROM "
                    . $this->model->getCurrentTenantConfig()->getRelationTablename() . " a "
                    . "WHERE fieldname = " . $this->quote($fieldname);
            }

            $subquery = "SELECT a.o_id FROM "
                . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition;

            $query .= " AND src IN (" . $subquery . ") GROUP BY dest";

            OnlineShop_Plugin::getSQLLogger()->log("Query: " . $query, Zend_Log::INFO);
            $result = $this->db->fetchAssoc($query);
            OnlineShop_Plugin::getSQLLogger()->log("Query done.", Zend_Log::INFO);
            return $result;
        } else {
            $query = "SELECT dest FROM " . $this->model->getCurrentTenantConfig()->getRelationTablename() . " a "
                . "WHERE fieldname = " . $this->quote($fieldname);

            $subquery = "SELECT a.o_id FROM "
                . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition;

            $query .= " AND src IN (" . $subquery . ") GROUP BY dest";

            OnlineShop_Plugin::getSQLLogger()->log("Query: " . $query, Zend_Log::INFO);
            $result = $this->db->fetchCol($query);
            OnlineShop_Plugin::getSQLLogger()->log("Query done.", Zend_Log::INFO);
            return $result;
        }
    }

    public function getCount($condition, $orderBy = null, $limit = null, $offset = null) {
        if($condition) {
            $condition = "WHERE " . $condition;
        }

        if($orderBy) {
            $orderBy = " ORDER BY " . $orderBy;
        }

        if($limit) {
            if($offset) {
                $limit = "LIMIT " . $offset . ", " . $limit;
            } else {
                $limit = "LIMIT " . $limit;
            }
        }

        if($this->model->getVariantMode() == OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            $query = "SELECT count(DISTINCT o_virtualProductId) FROM "
                . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . $orderBy . " " . $limit;
        } else {
            $query = "SELECT count(*) FROM "
                . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . $orderBy . " " . $limit;
        }
        OnlineShop_Plugin::getSQLLogger()->log("Query: " . $query, Zend_Log::INFO);
        $result = $this->db->fetchOne($query);
        OnlineShop_Plugin::getSQLLogger()->log("Query done.", Zend_Log::INFO);
        return $result;
    }

    public function quote($value) {
        return $this->db->quote($value);
    }

    /**
     * returns order by statement for simularity calculations based on given fields and object ids
     *
     * @param $fields
     * @param $objectId
     */
    public function buildSimularityOrderBy($fields, $objectId) {

        try {
            $fieldString = "";
            $maxFieldString = "";
            foreach($fields as $f) {
                if(!empty($fieldString)) {
                    $fieldString .= ",";
                    $maxFieldString .= ",";
                }
                $fieldString .= $this->db->quoteIdentifier($f->getField());
                $maxFieldString .= "MAX(" . $this->db->quoteIdentifier($f->getField()) . ") as " . $this->db->quoteIdentifier($f->getField());
            }

            $query = "SELECT " . $fieldString . " FROM " . $this->model->getCurrentTenantConfig()->getTablename() . " a WHERE a.o_id = ?;";
            OnlineShop_Plugin::getSQLLogger()->log("Query: " . $query, Zend_Log::INFO);
            $objectValues = $this->db->fetchRow($query, $objectId);
            OnlineShop_Plugin::getSQLLogger()->log("Query done.", Zend_Log::INFO);

            $query = "SELECT " . $maxFieldString . " FROM " . $this->model->getCurrentTenantConfig()->getTablename() . " a";
            OnlineShop_Plugin::getSQLLogger()->log("Query: " . $query, Zend_Log::INFO);
            $maxObjectValues = $this->db->fetchRow($query);
            OnlineShop_Plugin::getSQLLogger()->log("Query done.", Zend_Log::INFO);

            if(!empty($objectValues)) {
                $subStatement = array();
                foreach($fields as $f) {
                    $subStatement[] =
                        "(" .
                        $this->db->quoteIdentifier($f->getField()) . "/" . $maxObjectValues[$f->getField()] .
                        " - " .
                        $objectValues[$f->getField()] / $maxObjectValues[$f->getField()] .
                        ") * " . $f->getWeight();
                }

                $statement = "ABS(" . implode(" + ", $subStatement) . ")";

                OnlineShop_Plugin::getSQLLogger()->log("Similarity Statement: " . $statement, Zend_Log::INFO);
                return $statement;
            } else {
                throw new Exception("Field array for given object id is empty");
            }



        } catch(Exception $e) {
            OnlineShop_Plugin::getSQLLogger()->err($e);
            return "";
        }
    }

    /**
     * returns where statement for fulltext search index
     *
     * @param $fields
     * @param $searchstring
     */
    public function buildFulltextSearchWhere($fields, $searchstring) {
        $columnNames = array();
        foreach($fields as $c) {
            $columnNames[] = $this->db->quoteIdentifier($c);
        }
        return 'MATCH (' . implode(",", $columnNames) . ') AGAINST (' . $this->db->quote($searchstring) . ' IN BOOLEAN MODE)';
    }


    /**
     * get the record count for the last select query
     * @return int
     */
    public function getLastRecordCount()
    {
        return $this->lastRecordCount;
    }
}
