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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql;

use Monolog\Logger;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;

class Dao
{
    /**
     * @var \Pimcore\Db\ConnectionInterface
     */
    private $db;

    /**
     * @var DefaultMysql
     */
    private $model;

    /**
     * @var int
     */
    private $lastRecordCount;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(ProductListInterface $model, Logger $logger)
    {
        $this->model = $model;
        $this->db = \Pimcore\Db::get();

        $this->logger = $logger;
    }

    public function load($condition, $orderBy = null, $limit = null, $offset = null)
    {
        if ($condition) {
            $condition = 'WHERE ' . $condition;
        }

        if ($orderBy) {
            $orderBy = ' ORDER BY ' . $orderBy;
        }

        if ($limit) {
            if ($offset) {
                $limit = 'LIMIT ' . $offset . ', ' . $limit;
            } else {
                $limit = 'LIMIT ' . $limit;
            }
        }

        if ($this->model->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            if ($orderBy) {
                $query = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT o_virtualProductId as o_id, priceSystemName FROM '
                    . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . ' GROUP BY o_virtualProductId, priceSystemName' . $orderBy . ' ' . $limit;
            } else {
                $query = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT o_virtualProductId as o_id, priceSystemName FROM '
                    . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . ' ' . $limit;
            }
        } else {
            $query = 'SELECT SQL_CALC_FOUND_ROWS a.o_id, priceSystemName FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . $orderBy . ' ' . $limit;
        }
        $this->logger->info('Query: ' . $query);
        $result = $this->db->fetchAll($query);
        $this->lastRecordCount = (int)$this->db->fetchOne('SELECT FOUND_ROWS()');
        $this->logger->info('Query done.');

        return $result;
    }

    public function loadGroupByValues($fieldname, $condition, $countValues = false)
    {
        if ($condition) {
            $condition = 'WHERE ' . $condition;
        }

        if ($countValues) {
            if ($this->model->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                $query = "SELECT TRIM(`$fieldname`) as `value`, count(DISTINCT o_virtualProductId) as `count` FROM "
                    . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . ' GROUP BY TRIM(`' . $fieldname . '`)';
            } else {
                $query = "SELECT TRIM(`$fieldname`) as `value`, count(*) as `count` FROM "
                    . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . ' GROUP BY TRIM(`' . $fieldname . '`)';
            }

            $this->logger->info('Query: ' . $query);
            $result = $this->db->fetchAll($query);
            $this->logger->info('Query done.');

            return $result;
        } else {
            $query = 'SELECT ' . $this->db->quoteIdentifier($fieldname) . ' FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . ' GROUP BY ' . $this->db->quoteIdentifier($fieldname);

            $this->logger->info('Query: ' . $query);
            $result = $this->db->fetchCol($query);
            $this->logger->info('Query done.');

            return $result;
        }
    }

    public function loadGroupByRelationValues($fieldname, $condition, $countValues = false)
    {
        if ($condition) {
            $condition = 'WHERE ' . $condition;
        }

        if ($countValues) {
            if ($this->model->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                $query = 'SELECT dest as `value`, count(DISTINCT src_virtualProductId) as `count` FROM '
                    . $this->model->getCurrentTenantConfig()->getRelationTablename() . ' a '
                    . 'WHERE fieldname = ' . $this->quote($fieldname);
            } else {
                $query = 'SELECT dest as `value`, count(*) as `count` FROM '
                    . $this->model->getCurrentTenantConfig()->getRelationTablename() . ' a '
                    . 'WHERE fieldname = ' . $this->quote($fieldname);
            }

            $subquery = 'SELECT a.o_id FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition;

            $query .= ' AND src IN (' . $subquery . ') GROUP BY dest';

            $this->logger->info('Query: ' . $query);
            $result = $this->db->fetchAll($query);
            $this->logger->info('Query done.');

            return $result;
        } else {
            $query = 'SELECT dest FROM ' . $this->model->getCurrentTenantConfig()->getRelationTablename() . ' a '
                . 'WHERE fieldname = ' . $this->quote($fieldname);

            $subquery = 'SELECT a.o_id FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition;

            $query .= ' AND src IN (' . $subquery . ') GROUP BY dest';

            $this->logger->info('Query: ' . $query);
            $result = $this->db->fetchCol($query);
            $this->logger->info('Query done.');

            return $result;
        }
    }

    public function getCount($condition, $orderBy = null, $limit = null, $offset = null)
    {
        if ($condition) {
            $condition = 'WHERE ' . $condition;
        }

        if ($orderBy) {
            $orderBy = ' ORDER BY ' . $orderBy;
        }

        if ($limit) {
            if ($offset) {
                $limit = 'LIMIT ' . $offset . ', ' . $limit;
            } else {
                $limit = 'LIMIT ' . $limit;
            }
        }

        if ($this->model->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            $query = 'SELECT count(DISTINCT o_virtualProductId) FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . $orderBy . ' ' . $limit;
        } else {
            $query = 'SELECT count(*) FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . $orderBy . ' ' . $limit;
        }

        $this->logger->info('Query: ' . $query);
        $result = $this->db->fetchOne($query);
        $this->logger->info('Query done.');

        return $result;
    }

    public function quote($value)
    {
        return $this->db->quote($value);
    }

    /**
     * returns order by statement for simularity calculations based on given fields and object ids
     *
     * @param array $fields
     * @param int $objectId
     *
     * @return string
     */
    public function buildSimularityOrderBy($fields, $objectId)
    {
        try {
            $fieldString = '';
            $maxFieldString = '';
            foreach ($fields as $f) {
                if (!empty($fieldString)) {
                    $fieldString .= ',';
                    $maxFieldString .= ',';
                }
                $fieldString .= $this->db->quoteIdentifier($f->getField());
                $maxFieldString .= 'MAX(' . $this->db->quoteIdentifier($f->getField()) . ') as ' . $this->db->quoteIdentifier($f->getField());
            }

            $query = 'SELECT ' . $fieldString . ' FROM ' . $this->model->getCurrentTenantConfig()->getTablename() . ' a WHERE a.o_id = ?;';

            $this->logger->info('Query: ' . $query);
            $objectValues = $this->db->fetchRow($query, $objectId);
            $this->logger->info('Query done.');

            $query = 'SELECT ' . $maxFieldString . ' FROM ' . $this->model->getCurrentTenantConfig()->getTablename() . ' a';

            $this->logger->info('Query: ' . $query);
            $maxObjectValues = $this->db->fetchRow($query);
            $this->logger->info('Query done.');

            if (!empty($objectValues)) {
                $subStatement = [];
                foreach ($fields as $f) {
                    $subStatement[] =
                        '(' .
                        $this->db->quoteIdentifier($f->getField()) . '/' . $maxObjectValues[$f->getField()] .
                        ' - ' .
                        $objectValues[$f->getField()] / $maxObjectValues[$f->getField()] .
                        ') * ' . $f->getWeight();
                }

                $statement = 'ABS(' . implode(' + ', $subStatement) . ')';
                $this->logger->info('Similarity Statement: ' . $statement);

                return $statement;
            } else {
                throw new \Exception('Field array for given object id is empty');
            }
        } catch (\Exception $e) {
            $this->logger->err($e);

            return '';
        }
    }

    /**
     * returns where statement for fulltext search index
     *
     * @param array $fields
     * @param string $searchstring
     *
     * @return string
     */
    public function buildFulltextSearchWhere($fields, $searchstring)
    {
        $columnNames = [];
        foreach ($fields as $c) {
            $columnNames[] = $this->db->quoteIdentifier($c);
        }

        return 'MATCH (' . implode(',', $columnNames) . ') AGAINST (' . $this->db->quote($searchstring) . ' IN BOOLEAN MODE)';
    }

    /**
     * get the record count for the last select query
     *
     * @return int
     */
    public function getLastRecordCount()
    {
        return $this->lastRecordCount;
    }
}
