<?php

class OnlineShop_Framework_ProductList_Resource {

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    private $db;

    /**
     * @var OnlineShop_Framework_ProductList
     */
    private $model;

    public function __construct(OnlineShop_Framework_ProductList $model) {
        $this->model = $model;
        $this->db = Pimcore_Resource::get();
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

        if($this->model->getVariantMode() == OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            if($orderBy) {
                $query = "SELECT DISTINCT o_virtualProductId as o_id, priceSystemName FROM "
                    . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . " GROUP BY o_virtualProductId, priceSystemName" . $orderBy . " " . $limit;
            } else {
                $query = "SELECT DISTINCT o_virtualProductId as o_id, priceSystemName FROM "
                    . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . " " . $limit;
            }
        } else {
            $query = "SELECT a.o_id, priceSystemName FROM "
                . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . $orderBy . " " . $limit;
        }
        Logger::log("Query: " . $query, Zend_Log::INFO);
        $result = $this->db->fetchAll($query);
        Logger::log("Query done.", Zend_Log::INFO);
        return $result;
    }

    public function loadGroupByValues($fieldname, $condition, $countValues = false) {

        if($condition) {
            $condition = "WHERE " . $condition;
        }

        if($countValues) {
            if($this->model->getVariantMode() == OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
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

            Logger::log("Query: " . $query, Zend_Log::INFO);
            $result = $this->db->fetchAll($query);
            Logger::log("Query done.", Zend_Log::INFO);
            return $result;
        } else {
            $query = "SELECT `$fieldname` FROM "
                . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . " GROUP BY `" . $fieldname . "`";
            Logger::log("Query: " . $query, Zend_Log::INFO);
            $result = $this->db->fetchCol($query);
            Logger::log("Query done.", Zend_Log::INFO);
            return $result;
        }
    }

    public function loadGroupByRelationValues($fieldname, $condition, $countValues = false) {

        if($condition) {
            $condition = "WHERE " . $condition;
        }

        if($countValues) {
            if($this->model->getVariantMode() == OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
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

            Logger::log("Query: " . $query, Zend_Log::INFO);
            $result = $this->db->fetchAll($query);
            Logger::log("Query done.", Zend_Log::INFO);
            return $result;
        } else {
            $query = "SELECT dest FROM " . $this->model->getCurrentTenantConfig()->getRelationTablename() . " a "
                . "WHERE fieldname = " . $this->quote($fieldname);

            $subquery = "SELECT a.o_id FROM "
                . $this->model->getCurrentTenantConfig()->getTablename() . " a "
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition;

            $query .= " AND src IN (" . $subquery . ") GROUP BY dest";

            Logger::log("Query: " . $query, Zend_Log::INFO);
            $result = $this->db->fetchCol($query);
            Logger::log("Query done.", Zend_Log::INFO);
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

        if($this->model->getVariantMode() == OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
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
        Logger::log("Query: " . $query, Zend_Log::INFO);
        $result = $this->db->fetchOne($query);
        Logger::log("Query done.");
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
            Logger::log("Query: " . $query, Zend_Log::INFO);
            $objectValues = $this->db->fetchRow($query, $objectId);
            Logger::log("Query done.", Zend_Log::INFO);

            $query = "SELECT " . $maxFieldString . " FROM " . $this->model->getCurrentTenantConfig()->getTablename() . " a";
            Logger::log("Query: " . $query, Zend_Log::INFO);
            $maxObjectValues = $this->db->fetchRow($query);
            Logger::log("Query done.", Zend_Log::INFO);

            if(!empty($objectValues)) {
//                $subStatement = array();
//                foreach($fields as $f) {
//                    $subStatement[] = $f->getWeight() . " * " . $this->db->quoteIdentifier($f->getField()) . " * " . $objectValues[$f->getField()];
//                }
//
//                $firstPart = implode(" + ", $subStatement);
//
//
//                $subStatement = array();
//                foreach($fields as $f) {
//                    $subStatement[] = $f->getWeight() . " * POW(" . $this->db->quoteIdentifier($f->getField()) . ",2)";
//                }
//                $secondPart = "POW(" . implode(" + ", $subStatement) . ", 0.5)";
//
//                $subStatement = array();
//                foreach($fields as $f) {
//                    $subStatement[] = $f->getWeight() . " * POW(" . $objectValues[$f->getField()] . ",2)";
//                }
//
//                $secondPart .= " * POW(" . implode(" + ", $subStatement) . ", 0.5)";
//
//
//                $statement = "(" . $firstPart . ") / (" . $secondPart . ")";

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

                Logger::log("Similarity Statement: " . $statement, Zend_Log::INFO);
                return $statement;
            } else {
                throw new Exception("Field array for given object id is empty");
            }



        } catch(Exception $e) {
            Logger::err($e);
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
        return 'MATCH (' . implode(",", $columnNames) . ') AGAINST (' . $this->db->quote($searchstring) . ')';
    }
}
