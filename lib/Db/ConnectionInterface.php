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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Db;

use Doctrine\DBAL\Cache\CacheException;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Model\Element\ValidationException;

interface ConnectionInterface extends Connection
{
    /**
     * @param string $query
     * @param array $params
     * @param array $types
     * @param QueryCacheProfile|null $qcp
     *
     * @return ResultStatement
     *
     * @throws DBALException
     */
    public function executeQuery($query, array $params = [], $types = [], QueryCacheProfile $qcp = null);

    /**
     * @param string $query
     * @param array $params
     * @param array $types
     *
     * @return int
     *
     * @throws DBALException
     */
    public function executeUpdate($query, array $params = [], array $types = []);

    /**
     * @param string $query
     * @param array $params
     * @param array $types
     * @param QueryCacheProfile $qcp
     *
     * @return ResultStatement
     *
     * @throws CacheException
     */
    public function executeCacheQuery($query, $params, $types, QueryCacheProfile $qcp);

    /**
     * @param string $tableExpression
     * @param array $data
     * @param array $identifier
     * @param array $types
     *
     * @return int
     *
     * @throws DBALException
     */
    public function update($tableExpression, array $data, array $identifier, array $types = []);

    /**
     * @param string $tableExpression
     * @param array $data
     * @param array $types
     *
     * @return int
     *
     * @throws DBALException
     */
    public function insert($tableExpression, array $data, array $types = []);

    /**
     * @param string $table
     * @param string $where
     *
     * @return int
     *
     * @throws DBALException
     */
    public function deleteWhere($table, $where = '');

    /**
     * @param string $table
     * @param array $data
     * @param string $where
     *
     * @return int
     *
     * @throws DBALException
     */
    public function updateWhere($table, array $data, $where = '');

    /**
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return mixed
     */
    public function fetchRow($sql, $params = [], $types = []);

    /**
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return mixed
     *
     * @throws DBALException
     * @throws DriverException
     */
    public function fetchCol($sql, $params = [], $types = []);

    /**
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return mixed
     *
     * @throws DBALException
     */
    public function fetchOne($sql, $params = [], $types = []);

    /**
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return array
     *
     * @throws DBALException
     * @throws DriverException
     */
    public function fetchPairs($sql, array $params = [], $types = []);

    /**
     * @param string $table
     * @param array $data
     *
     * @return int
     *
     * @throws DBALException
     */
    public function insertOrUpdate($table, array $data);

    /**
     * @param string $str
     *
     * @return string
     */
    public function quoteIdentifier($str);

    /**
     * @param string $text
     * @param mixed $value
     * @param string|null $type
     * @param int|null $count
     *
     * @return string
     */
    public function quoteInto($text, $value, $type = null, $count = null);

    /**
     * @param string|array $ident
     * @param string $alias
     *
     * @return string
     */
    public function quoteColumnAs($ident, $alias);

    /**
     * @param string $ident
     * @param string|null $alias
     *
     * @return string
     */
    public function quoteTableAs($ident, $alias = null);

    /**
     * @param string $sql
     * @param int $count
     * @param int $offset
     *
     * @return string
     */
    public function limit($sql, $count, $offset = 0);

    /**
     * @param string $sql
     * @param array $exclusions
     *
     * @return ResultStatement
     *
     * @throws ValidationException
     */
    public function queryIgnoreError($sql, $exclusions = []);

    /**
     * @param bool $autoQuoteIdentifiers
     *
     * @return void
     */
    public function setAutoQuoteIdentifiers($autoQuoteIdentifiers);

    /**
     * @param string $statement
     * @param mixed[] $params
     * @param int[]|string[] $types
     *
     * @return mixed[]|false
     */
    public function fetchAssoc($statement, array $params = [], array $types = []);

    /**
     * @param string $statement
     * @param mixed[] $params
     * @param int[]|string[] $types
     *
     * @return mixed[]|false
     */
    public function fetchArray($statement, array $params = [], array $types = []);

    /**
     * @param string $statement
     * @param mixed[] $params
     * @param int $column
     * @param int[]|string[] $types
     *
     * @return  mixed|false
     */
    public function fetchColumn($statement, array $params = [], $column = 0, array $types = []);

    /**
     * @param string $tableExpression
     * @param mixed[] $identifier
     * @param int[]|string[] $types
     *
     * @return mixed
     */
    public function delete($tableExpression, array $identifier, array $types = []);

    /**
     * @param string $sql
     * @param mixed[] $params
     * @param int[]|string[] $types
     *
     * @return mixed
     */
    public function fetchAll($sql, array $params = [], $types = []);

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder();

    /**
     * @return void
     */
    public function close();

    /**
     * @param string $table
     * @param string $idColumn
     * @param string $where
     */
    public function selectAndDeleteWhere($table, $idColumn = 'id', $where = '');

    /**
     * @return string
     */
    public function getDatabase();

    /**
     * @param string $like
     *
     * @return string
     */
    public function escapeLike(string $like): string;

    /**
     * @return \PDO
     */
    public function getWrappedConnection();
}
