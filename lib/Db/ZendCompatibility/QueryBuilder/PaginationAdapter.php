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

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_Paginator_Adapter_DbSelect
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Db\ZendCompatibility\QueryBuilder;

use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Db\ZendCompatibility\QueryBuilder;
use Zend\Paginator\Adapter\AdapterInterface;

class PaginationAdapter implements AdapterInterface
{
    /**
     * Name of the row count column
     *
     * @var string
     */
    const ROW_COUNT_COLUMN = 'zend_compability_paginator_row_count';

    /**
     * The COUNT query
     *
     * @var QueryBuilder
     */
    protected $_countSelect = null;

    /**
     * Database query
     *
     * @var QueryBuilder
     */
    protected $_select = null;

    /**
     * Total item count
     *
     * @var int
     */
    protected $_rowCount = null;

    /**
     * Identifies this adapter for caching purposes.  This value will remain constant for
     * the entire life of this adapter regardless of how many different pages are queried.
     *
     * @var string
     */
    protected $_cacheIdentifier = null;

    /**
     * Constructor.
     *
     * @param QueryBuilder $select The select query
     */
    public function __construct(QueryBuilder $select)
    {
        $this->_select = $select;
        $this->_cacheIdentifier = md5($select->assemble());
    }

    /**
     * Returns the cache identifier.
     *
     * @return string
     */
    public function getCacheIdentifier()
    {
        return $this->_cacheIdentifier;
    }

    /**
     * Sets the total row count, either directly or through a supplied
     * query.  Without setting this, {@link getPages()} selects the count
     * as a subquery (SELECT COUNT ... FROM (SELECT ...)).  While this
     * yields an accurate count even with queries containing clauses like
     * LIMIT, it can be slow in some circumstances.  For example, in MySQL,
     * subqueries are generally slow when using the InnoDB storage engine.
     * Users are therefore encouraged to profile their queries to find
     * the solution that best meets their needs.
     *
     * @param  QueryBuilder|int $totalRowCount Total row count integer
     *                                               or query
     *
     * @return PaginationAdapter $this
     *
     * @throws \Exception
     */
    public function setRowCount($rowCount)
    {
        if ($rowCount instanceof QueryBuilder) {
            $columns = $rowCount->getPart(QueryBuilder::COLUMNS);

            $countColumnPart = empty($columns[0][2])
                ? $columns[0][1]
                : $columns[0][2];

            if ($countColumnPart instanceof Expression) {
                $countColumnPart = $countColumnPart->__toString();
            }

            $rowCountColumn = self::ROW_COUNT_COLUMN;

            // The select query can contain only one column, which should be the row count column
            if (false === strpos($countColumnPart, $rowCountColumn)) {
                throw new \Exception('Row count column not found');
            }

            $result = $rowCount->query(\PDO::FETCH_ASSOC)->fetch();

            $this->_rowCount = count($result) > 0 ? $result[$rowCountColumn] : 0;
        } elseif (is_integer($rowCount)) {
            $this->_rowCount = $rowCount;
        } else {
            throw new \Exception('Invalid row count');
        }

        return $this;
    }

    /**
     * Returns an array of items for a page.
     *
     * @param  int $offset Page offset
     * @param  int $itemCountPerPage Number of items per page
     *
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->_select->limit($itemCountPerPage, $offset);

        return $this->_select->query()->fetchAll();
    }

    /**
     * Returns the total number of rows in the result set.
     *
     * @return int
     */
    public function count()
    {
        if ($this->_rowCount === null) {
            $this->setRowCount(
                $this->getCountSelect()
            );
        }

        return $this->_rowCount;
    }

    /**
     * Get the COUNT select object for the provided query
     *
     * TODO: Have a look at queries that have both GROUP BY and DISTINCT specified.
     * In that use-case I'm expecting problems when either GROUP BY or DISTINCT
     * has one column.
     *
     * @return QueryBuilder
     */
    public function getCountSelect()
    {
        /**
         * We only need to generate a COUNT query once. It will not change for
         * this instance.
         */
        if ($this->_countSelect !== null) {
            return $this->_countSelect;
        }

        $rowCount = clone $this->_select;
        $rowCount->__toString(); // Workaround for ZF-3719 and related

        $db = $rowCount->getAdapter();

        $countColumn = $db->quoteIdentifier(self::ROW_COUNT_COLUMN);
        $countPart   = 'COUNT(1) AS ';
        $groupPart   = null;
        $unionParts  = $rowCount->getPart(QueryBuilder::UNION);

        /**
         * If we're dealing with a UNION query, execute the UNION as a subquery
         * to the COUNT query.
         */
        if (!empty($unionParts)) {
            $expression = new Expression($countPart . $countColumn);

            $rowCount = $db
                ->select()
                ->bind($rowCount->getBind())
                ->from($rowCount, $expression);
        } else {
            $columnParts = $rowCount->getPart(QueryBuilder::COLUMNS);
            $groupParts  = $rowCount->getPart(QueryBuilder::GROUP);
            $havingParts = $rowCount->getPart(QueryBuilder::HAVING);
            $isDistinct  = $rowCount->getPart(QueryBuilder::DISTINCT);

            /**
             * If there is more than one column AND it's a DISTINCT query, more
             * than one group, or if the query has a HAVING clause, then take
             * the original query and use it as a subquery os the COUNT query.
             */
            if (($isDistinct && ((count($columnParts) == 1 && $columnParts[0][1] == QueryBuilder::SQL_WILDCARD)
                        || count($columnParts) > 1)) || count($groupParts) > 1 || !empty($havingParts)) {
                $rowCount->reset(QueryBuilder::ORDER);
                $rowCount = $db
                    ->select()
                    ->bind($rowCount->getBind())
                    ->from($rowCount);
            } elseif ($isDistinct) {
                $part = $columnParts[0];

                if ($part[1] !== QueryBuilder::SQL_WILDCARD && !($part[1] instanceof Expression)) {
                    $column = $db->quoteIdentifier($part[1], true);

                    if (!empty($part[0])) {
                        $column = $db->quoteIdentifier($part[0], true) . '.' . $column;
                    }

                    $groupPart = $column;
                }
            } elseif (!empty($groupParts)) {
                $groupPart = $db->quoteIdentifier($groupParts[0], true);
            }

            /**
             * If the original query had a GROUP BY or a DISTINCT part and only
             * one column was specified, create a COUNT(DISTINCT ) query instead
             * of a regular COUNT query.
             */
            if (!empty($groupPart)) {
                $countPart = 'COUNT(DISTINCT ' . $groupPart . ') AS ';
            }

            /**
             * Create the COUNT part of the query
             */
            $expression = new Expression($countPart . $countColumn);

            $rowCount->reset(QueryBuilder::COLUMNS)
                ->reset(QueryBuilder::ORDER)
                ->reset(QueryBuilder::LIMIT_OFFSET)
                ->reset(QueryBuilder::GROUP)
                ->reset(QueryBuilder::DISTINCT)
                ->reset(QueryBuilder::HAVING)
                ->columns($expression);
        }

        $this->_countSelect = $rowCount;

        return $rowCount;
    }
}
