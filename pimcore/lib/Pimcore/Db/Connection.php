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

namespace Pimcore\Db;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Pimcore\Db;
use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Db\ZendCompatibility\QueryBuilder;
use Pimcore\Db\ZendCompatibility\QueryBuilder as ZendDbCompatibleQueryBuilder;

class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * Specifies whether the connection automatically quotes identifiers.
     * If true, the methods insert(), update() apply identifier quoting automatically.
     * If false, developer must quote identifiers themselves by calling quoteIdentifier().
     *
     * @var bool
     */
    protected $autoQuoteIdentifiers = true;

    /**
     * @inheritdoc
     */
    public function connect()
    {
        $returnValue = parent::connect();

        if ($returnValue) {
            $this->_conn->query('SET default_storage_engine=InnoDB;');
            $this->_conn->query("SET sql_mode = '';");
        }

        return $returnValue;
    }

    /**
     * @inheritdoc
     */
    public function query()
    {
        $args = func_get_args();

        // compatibility layer for additional parameters in the 2nd argument
        // eg. $db->query("UPDATE myTest SET date = ? WHERE uri = ?", [time(), $uri]);
        if (func_num_args() === 2) {
            if (is_array($args[1])) {
                return $this->executeQuery($args[0], $args[1]);
            }
        }

        if (count($args) > 0) {
            $args[0] = $this->normalizeQuery($args[0], [], true);
        }

        return call_user_func_array([$this, 'parent::query'], $args);
    }

    /**
     * @inheritDoc
     */
    public function executeQuery($query, array $params = [], $types = [], QueryCacheProfile $qcp = null)
    {
        list($query, $params) = $this->normalizeQuery($query, $params);

        return parent::executeQuery($query, $params, $types, $qcp);
    }

    /**
     * @inheritDoc
     */
    public function executeCacheQuery($query, $params, $types, QueryCacheProfile $qcp)
    {
        list($query, $params) = $this->normalizeQuery($query, $params);

        return parent::executeCacheQuery($query, $params, $types, $qcp);
    }

    /**
     * @param string|QueryBuilder $query
     * @param array $params
     * @param bool $onlyQuery
     *
     * @return array|string
     */
    private function normalizeQuery($query, array $params = [], $onlyQuery = false)
    {
        // stringify query builder
        if ($query instanceof QueryBuilder) {
            $qb     = $query;
            $query  = $qb->getSQL();
            $params = array_merge($qb->getParameters(), $params);

            Db::getLogger()->debug('QueryBuilder instance was normalized to string.', [
                'query'  => $query,
                'params' => $params
            ]);
        }

        if ($onlyQuery) {
            return $query;
        }

        return [$query, $params];
    }

    /**
     * @inheritdoc
     */
    public function update($tableExpression, array $data, array $identifier, array $types = [])
    {
        $data = $this->quoteDataIdentifiers($data);
        $identifier = $this->quoteDataIdentifiers($identifier);

        return parent::update($tableExpression, $data, $identifier, $types);
    }

    /**
     * @inheritdoc
     */
    public function insert($tableExpression, array $data, array $types = [])
    {
        $data = $this->quoteDataIdentifiers($data);

        return parent::insert($tableExpression, $data, $types);
    }

    /**
     * Deletes table rows based on a custom WHERE clause.
     *
     * @param  mixed        $table The table to update.
     * @param  mixed        $where DELETE WHERE clause(s).
     *
     * @return int          The number of affected rows.
     */
    public function deleteWhere($table, $where = '')
    {
        $sql = 'DELETE FROM ' . $table;
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }

        return $this->executeUpdate($sql);
    }

    /**
     * Updates table rows with specified data based on a custom WHERE clause.
     *
     * @param  mixed        $table The table to update.
     * @param  array        $data  Column-value pairs.
     * @param  mixed        $where UPDATE WHERE clause(s).
     *
     * @return int          The number of affected rows.
     */
    public function updateWhere($table, array $data, $where = '')
    {
        $set = [];
        $paramValues = [];

        foreach ($data as $columnName => $value) {
            $set[] = $this->quoteIdentifier($columnName) . ' = ?';
            $paramValues[] = $value;
        }

        $sql  = 'UPDATE ' . $table . ' SET ' . implode(', ', $set);

        if ($where) {
            $sql .= ' WHERE ' . $where;
        }

        return $this->executeUpdate($sql, $paramValues);
    }

    /**
     * Fetches the first row of the SQL result.
     *
     * @param $sql
     * @param array $params
     * @param array $types
     *
     * @return mixed
     */
    public function fetchRow($sql, $params = [], $types = [])
    {
        $params = $this->prepareParams($params);

        return $this->executeQuery($sql, $params, $types)->fetch();
    }

    /**
     * Fetches the first column of all SQL result rows as an array.
     *
     * @param $sql
     * @param array $params
     * @param array $types
     *
     * @return mixed
     */
    public function fetchCol($sql, $params = [], $types = [])
    {
        $params = $this->prepareParams($params);

        // unfortunately Mysqli driver doesn't support \PDO::FETCH_COLUMN, so we have to do it manually
        $stmt = $this->executeQuery($sql, $params, $types);
        $data = [];
        while ($row = $stmt->fetchColumn()) {
            $data[] = $row;
        }

        $stmt->closeCursor();

        return $data;
    }

    /**
     * Fetches the first column of the first row of the SQL result.
     *
     * @param $sql
     * @param array $params
     * @param array $types
     *
     * @return mixed
     */
    public function fetchOne($sql, $params = [], $types = [])
    {
        $params = $this->prepareParams($params);
        // unfortunately Mysqli driver doesn't support \PDO::FETCH_COLUMN, so we have to use $this->fetchColumn() instead
        return $this->fetchColumn($sql, $params, 0, $types);
    }

    /**
     * Fetches all SQL result rows as an array of key-value pairs.
     *
     * The first column is the key, the second column is the
     * value.
     *
     * @param $sql
     * @param array $params
     * @param array $types
     *
     * @return array
     */
    public function fetchPairs($sql, array $params = [], $types = [])
    {
        $params = $this->prepareParams($params);
        $statement = $this->executeQuery($sql, $params, $types);
        $data = [];
        while ($row = $statement->fetch(\PDO::FETCH_NUM)) {
            $data[$row[0]] = $row[1];
        }

        return $data;
    }

    /**
     * @param $table
     * @param array $data
     *
     * @return int
     *
     * @throws \Exception
     */
    public function insertOrUpdate($table, array $data)
    {
        // extract and quote col names from the array keys
        $i = 0;
        $bind = [];
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $this->quoteIdentifier($col);
            $bind[':col' . $i] = $val;
            $vals[] = ':col' . $i;
            $i++;
        }

        // build the statement
        $set = [];
        foreach ($cols as $i => $col) {
            $set[] = sprintf('%s = %s', $col, $vals[$i]);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s;',
            $this->quoteIdentifier($table),
            implode(', ', $cols),
            implode(', ', $vals),
            implode(', ', $set)
        );

        $bind = array_merge($bind, $bind);

        $stmt = $this->executeQuery($sql, $bind);
        $result = $stmt->rowCount();

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function quoteIdentifier($str)
    {
        if ($str instanceof Expression) {
            return (string) $str;
        } elseif ($str instanceof QueryBuilder) {
            return '(' . $str->assemble() . ')';
        }

        return parent::quoteIdentifier($str);
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     *
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.   For example:
     *
     * <code>
     * $text = "WHERE date < ?";
     * $date = "2005-01-02";
     * $safe = $sql->quoteInto($text, $date);
     * // $safe = "WHERE date < '2005-01-02'"
     * </code>
     *
     * @param string  $text  The text with a placeholder.
     * @param mixed   $value The value to quote.
     * @param string  $type  OPTIONAL SQL datatype
     * @param int $count OPTIONAL count of placeholders to replace
     *
     * @return string An SQL-safe quoted value placed into the original text.
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        if ($count === null) {
            return str_replace('?', $this->quote($value, $type), $text);
        } else {
            return implode($this->quote($value, $type), explode('?', $text, $count + 1));
        }
    }

    /**
     * Quote a column identifier and alias.
     *
     * @param string|array $ident The identifier or expression.
     * @param string $alias An alias for the column.
     *
     * @return string The quoted identifier and alias.
     */
    public function quoteColumnAs($ident, $alias)
    {
        return $this->_quoteIdentifierAs($ident, $alias);
    }

    /**
     * Quote a table identifier and alias.
     *
     * @param string|array $ident The identifier or expression.
     * @param string $alias An alias for the table.
     *
     * @return string The quoted identifier and alias.
     */
    public function quoteTableAs($ident, $alias = null)
    {
        return $this->_quoteIdentifierAs($ident, $alias);
    }

    /**
     * Quote an identifier and an optional alias.
     *
     * @param string|array|Expression $ident The identifier or expression.
     * @param string $alias An optional alias.
     * @param bool $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @param string $as The string to add between the identifier/expression and the alias.
     *
     * @return string The quoted identifier and alias.
     */
    protected function _quoteIdentifierAs($ident, $alias = null, $auto = false, $as = ' AS ')
    {
        if ($ident instanceof Expression) {
            $quoted = $ident->__toString();
        } elseif ($ident instanceof QueryBuilder) {
            $quoted = '(' . $ident->assemble() . ')';
        } else {
            if (is_string($ident)) {
                $ident = explode('.', $ident);
            }
            if (is_array($ident)) {
                $segments = [];
                foreach ($ident as $segment) {
                    if ($segment instanceof Expression) {
                        $segments[] = $segment->__toString();
                    } else {
                        $segments[] = $this->_quoteIdentifier($segment, $auto);
                    }
                }
                if ($alias !== null && end($ident) == $alias) {
                    $alias = null;
                }
                $quoted = implode('.', $segments);
            } else {
                $quoted = $this->_quoteIdentifier($ident, $auto);
            }
        }
        if ($alias !== null) {
            $quoted .= $as . $this->_quoteIdentifier($alias, $auto);
        }

        return $quoted;
    }

    /**
     * Quote an identifier.
     *
     * @param  string $value The identifier or expression.
     * @param bool $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     *
     * @return string        The quoted identifier and alias.
     */
    protected function _quoteIdentifier($value, $auto=false)
    {
        if ($auto === false) {
            $q = '`';

            return $q . str_replace("$q", "$q$q", $value) . $q;
        }

        return $value;
    }

    /**
     * Returns a ZF1 compatible query builder
     * To use the standard Doctrine QueryBuilder, please use $dbal->createQueryBuilder() instead
     *
     * @return ZendDbCompatibleQueryBuilder
     */
    public function select()
    {
        return new ZendDbCompatibleQueryBuilder($this);
    }

    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     *
     * @param  string $sql
     * @param  int $count
     * @param  int $offset OPTIONAL
     *
     * @throws \Exception
     *
     * @return string
     */
    public function limit($sql, $count, $offset = 0)
    {
        $count = intval($count);
        if ($count <= 0) {
            throw new \Exception("LIMIT argument count=$count is not valid");
        }

        $offset = intval($offset);
        if ($offset < 0) {
            throw new \Exception("LIMIT argument offset=$offset is not valid");
        }

        $sql .= " LIMIT $count";
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }

        return $sql;
    }

    /**
     * @param $sql
     * @param array $bind
     *
     * @return mixed|null
     */
    public function queryIgnoreError($sql, $bind = [])
    {
        try {
            $return = $this->executeQuery($sql, $bind);

            return $return;
        } catch (\Exception $e) {
            // we simply ignore the error
        }

        return null;
    }

    /**
     * @param $params
     *
     * @return array
     */
    protected function prepareParams($params)
    {
        if (is_scalar($params)) {
            $params = [$params];
        }

        return $params;
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function quoteDataIdentifiers($data)
    {
        if (!$this->autoQuoteIdentifiers) {
            return $data;
        }

        $newData = [];
        foreach ($data as $key => $value) {
            $newData[$this->quoteIdentifier($key)] = $value;
        }

        return $newData;
    }

    /**
     * @param bool $autoQuoteIdentifiers
     */
    public function setAutoQuoteIdentifiers($autoQuoteIdentifiers)
    {
        $this->autoQuoteIdentifiers = $autoQuoteIdentifiers;
    }
}
