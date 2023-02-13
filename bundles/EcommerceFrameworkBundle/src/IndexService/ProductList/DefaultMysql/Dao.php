<?php

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql;

use Doctrine\DBAL\Connection;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class Dao
{
    private Connection $db;

    private DefaultMysql $model;

    private int $lastRecordCount;

    protected LoggerInterface $logger;

    public function __construct(DefaultMysql $model, LoggerInterface $logger)
    {
        $this->model = $model;
        $this->db = \Pimcore\Db::get();

        $this->logger = $logger;
    }

    public function load(string $condition, ?string $orderBy = null, ?int $limit = null, int $offset = 0): array
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
                $query = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT virtualProductId as id, priceSystemName FROM '
                    . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . ' GROUP BY virtualProductId, priceSystemName' . $orderBy . ' ' . $limit;
            } else {
                $query = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT virtualProductId as id, priceSystemName FROM '
                    . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                    . $this->model->getCurrentTenantConfig()->getJoins()
                    . $condition . ' ' . $limit;
            }
        } else {
            $query = 'SELECT SQL_CALC_FOUND_ROWS a.id, priceSystemName FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . $orderBy . ' ' . $limit;
        }
        $this->logger->info('Query: ' . $query);
        $result = $this->db->fetchAllAssociative($query);
        $this->lastRecordCount = (int)$this->db->fetchOne('SELECT FOUND_ROWS()');
        $this->logger->info('Query done.');

        return $result;
    }

    public function loadGroupByValues(string $fieldname, string $condition, bool $countValues = false): array
    {
        if ($condition) {
            $condition = 'WHERE ' . $condition;
        }

        if ($countValues) {
            if ($this->model->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                $query = "SELECT TRIM(`$fieldname`) as `value`, count(DISTINCT virtualProductId) as `count` FROM "
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
            $result = $this->db->fetchAllAssociative($query);
            $this->logger->info('Query done.');

            return $result;
        } else {
            $query = 'SELECT ' . $this->db->quoteIdentifier($fieldname) . ' FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition . ' GROUP BY ' . $this->db->quoteIdentifier($fieldname);

            $this->logger->info('Query: ' . $query);
            $result = $this->db->fetchFirstColumn($query);
            $this->logger->info('Query done.');

            return $result;
        }
    }

    public function loadGroupByRelationValues(string $fieldname, string $condition, bool $countValues = false): array
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

            $subquery = 'SELECT a.id FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition;

            $query .= ' AND src IN (' . $subquery . ') GROUP BY dest';

            $this->logger->info('Query: ' . $query);
            $result = $this->db->fetchAllAssociative($query);
            $this->logger->info('Query done.');

            return $result;
        } else {
            $query = 'SELECT dest FROM ' . $this->model->getCurrentTenantConfig()->getRelationTablename() . ' a '
                . 'WHERE fieldname = ' . $this->quote($fieldname);

            $subquery = 'SELECT a.id FROM '
                . $this->model->getCurrentTenantConfig()->getTablename() . ' a '
                . $this->model->getCurrentTenantConfig()->getJoins()
                . $condition;

            $query .= ' AND src IN (' . $subquery . ') GROUP BY dest';

            $this->logger->info('Query: ' . $query);
            $result = $this->db->fetchFirstColumn($query);
            $this->logger->info('Query done.');

            return $result;
        }
    }

    public function getCount(string $condition, ?string $orderBy = null, ?int $limit = null, int $offset = 0): int
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
            $query = 'SELECT count(DISTINCT virtualProductId) FROM '
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

        return is_int($result) ? $result : 0;
    }

    public function quote(mixed $value): mixed
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
    public function buildSimularityOrderBy(array $fields, int $objectId): string
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

            $query = 'SELECT ' . $fieldString . ' FROM ' . $this->model->getCurrentTenantConfig()->getTablename() . ' a WHERE a.id = ?;';

            $this->logger->info('Query: ' . $query);
            $objectValues = $this->db->fetchAssociative($query, [$objectId]);
            $this->logger->info('Query done.');

            $query = 'SELECT ' . $maxFieldString . ' FROM ' . $this->model->getCurrentTenantConfig()->getTablename() . ' a';

            $this->logger->info('Query: ' . $query);
            $maxObjectValues = $this->db->fetchAssociative($query);
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
            $this->logger->error((string) $e);

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
    public function buildFulltextSearchWhere(array $fields, string $searchstring): string
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
    public function getLastRecordCount(): int
    {
        return $this->lastRecordCount;
    }
}
