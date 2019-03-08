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
use Doctrine\DBAL\Driver\Connection;

interface ConnectionInterface extends Connection
{
    public function executeQuery($query, array $params = [], $types = [], QueryCacheProfile $qcp = null);

    public function executeUpdate($query, array $params = [], array $types = []);

    public function executeCacheQuery($query, $params, $types, QueryCacheProfile $qcp);

    public function update($tableExpression, array $data, array $identifier, array $types = []);

    public function insert($tableExpression, array $data, array $types = []);

    public function deleteWhere($table, $where = '');

    public function updateWhere($table, array $data, $where = '');

    public function fetchRow($sql, $params = [], $types = []);

    public function fetchCol($sql, $params = [], $types = []);

    public function fetchOne($sql, $params = [], $types = []);

    public function fetchPairs($sql, array $params = [], $types = []);

    public function insertOrUpdate($table, array $data);

    public function quoteIdentifier($str);

    public function quoteInto($text, $value, $type = null, $count = null);

    public function quoteColumnAs($ident, $alias);

    public function quoteTableAs($ident, $alias = null);

    public function select();

    public function supportsParameters();

    public function limit($sql, $count, $offset = 0);

    public function queryIgnoreError($sql, $exclusions = []);

    public function setAutoQuoteIdentifiers($autoQuoteIdentifiers);

    public function fetchAssoc($statement, array $params = [], array $types = []);

    public function fetchArray($statement, array $params = [], array $types = []);

    public function fetchColumn($statement, array $params = [], $column = 0, array $types = []);

    public function delete($tableExpression, array $identifier, array $types = []);

    public function fetchAll($sql, array $params = [], $types = []);

    public function createQueryBuilder();

    public function close();
}
