<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Adapter;

use Doctrine\DBAL\Connection;
use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Db;
use stdClass;

/**
 * @internal
 */
class Sql extends AbstractAdapter
{
    public function getData(
        ?array $filters,
        ?string $sort,
        ?string $dir,
        ?int $offset,
        ?int $limit,
        ?array $fields = null,
        ?array $drillDownFilters = null
    ): array {
        $db = Db::get();

        $baseQuery = $this->getBaseQuery($filters ?? [], $fields ?? [], false, $drillDownFilters ?? []);
        $data = [];
        $total = 0;

        if ($baseQuery) {
            $total = $db->fetchOne($baseQuery['count']);

            $order = '';
            if ($sort && $dir) {
                $dir = ((strtoupper($dir) === 'ASC') ? 'ASC' : 'DESC');
                $order = ' ORDER BY ' . $db->quoteIdentifier($sort) . ' ' .$dir;
            }

            $sql = $baseQuery['data'] . $order;
            if ($offset !== null && $limit) {
                $sql .= ' LIMIT ' . (int) $offset . ',' . (int) $limit;
            }

            $data = $db->fetchAllAssociative($sql);
        }

        return ['data' => $data, 'total' => $total];
    }

    public function getColumns(?stdClass $configuration): array
    {
        $sql = '';
        if ($configuration) {
            $sql = $this->buildQueryString($configuration);
        }
        $sqlStripped = $this->stripSqlCommentsForValidation($sql);

        if (
            !preg_match('/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE)\s/i', $sqlStripped, $matches)
        ) {
            $columnNames = $this->getResolvedColumnNames($sql, Db::get());
            $this->assertResolvedColumnsAllowed($columnNames);

            return $columnNames;
        }

        throw new Exception("Only 'SELECT' statements are allowed! You've used '" . $matches[0] . "'");
    }

    protected function buildQueryString(
        stdClass $config,
        bool $ignoreSelectAndGroupBy = false,
        ?array $drillDownFilters = null,
        ?string $selectField = null
    ): string {
        $config = (array) $config;
        $sql = '';

        foreach (['sql', 'from', 'where', 'groupby'] as $key) {
            if (!empty($config[$key])) {
                if (!is_string($config[$key])) {
                    throw new InvalidArgumentException(sprintf('Invalid "%s" SQL fragment; expected string.', $key));
                }

                try {
                    $this->validateSqlFragment($config[$key]);
                    $this->validateAgainstDenyList($config[$key]);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException(sprintf('Unsafe "%s" SQL fragment: %s', $key, $e->getMessage()), 0, $e);
                }
            }
        }

        if (!empty($config['sql']) && !$ignoreSelectAndGroupBy) {
            if (!str_starts_with(strtoupper(trim($config['sql'])), 'SELECT')) {
                $sql .= 'SELECT';
            }
            $sql .= "\n" . $config['sql'];
        } elseif ($selectField) {
            $this->validateAgainstDenyList($selectField);

            $db = Db::get();
            $sql .= 'SELECT ' . $db->quoteIdentifier($selectField);
        } else {
            $sql .= 'SELECT *';
        }

        if (!empty($config['from'])) {
            if (!str_starts_with(strtoupper(trim($config['from'])), 'FROM')) {
                $sql .= "\nFROM ";
            }
            $sql .= "\n" . $config['from'];
        }

        if (!empty($config['where'])) {
            if (str_starts_with(strtoupper(trim($config['where'])), 'WHERE')) {
                $config['where'] = preg_replace('/^\s*WHERE\s*/i', '', $config['where']);
            }

            $sql .= "\nWHERE (" . $config['where'] . ')';
        }

        if (!empty($config['groupby']) && !$ignoreSelectAndGroupBy) {
            if (!str_starts_with(strtoupper(trim($config['groupby'])), 'GROUP BY')) {
                $sql .= "\nGROUP BY ";
            }

            $sql .= "\n" . $config['groupby'];
        }

        if ($drillDownFilters) {
            $havingParts = [];
            $db = Db::get();

            foreach ($drillDownFilters as $field => $value) {
                if ($value === '' || $value === null) {
                    continue;
                }

                $havingParts[] =
                    $db->quoteIdentifier($field)
                    . ' = '
                    . $db->quote($value);
            }

            if ($havingParts) {
                $sql .= "\nHAVING " . implode(' AND ', $havingParts);
            }
        }

        return $sql;
    }

    private function validateSqlFragment(string $sql): void
    {
        // Remove quoted strings/identifiers to avoid false positives (e.g. INSERT() function, literals containing "--", "#", ";", etc.)
        // Backslash is excluded from the catch-all and consumed together with the character it escapes
        // (\\.) so runs of consecutive backslashes are paired up the same way MySQL parses them - a
        // one-off "[^'] excludes only the quote" version mismatches on an even number of backslashes
        // and can misjudge where the literal actually closes.
        $sqlForValidation = preg_replace(
            [
                "/'(?:''|\\\\.|[^'\\\\])*'/s",
                '/"(?:""|\\\\.|[^"\\\\])*"/s',
                '/`[^`]*`/s',
            ],
            ["''", '""', '``'],
            $sql
        ) ?? $sql;

        // Normalize whitespace/newlines for consistent boundary checking
        $sqlForValidation = preg_replace('/\s+/s', ' ', $sqlForValidation) ?? $sqlForValidation;
        $forbiddenPatterns = [
            '/;/',
            '/--\s/', // comment start (MySQL-style, requires whitespace after --)
            '/#/',
            '/\/\*/',
            '/\*\//',
            '/^\s*DROP\b/i',
            '/^\s*DELETE\s+FROM\b/i',
            '/^\s*UPDATE\s+\S+\s+SET\b/i',
            '/^\s*INSERT\s+INTO\b/i',
            '/^\s*ALTER\b/i',
            '/^\s*CREATE\b/i',
            '/^\s*TRUNCATE\b/i',
        ];

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $sqlForValidation)) {
                throw new InvalidArgumentException('Unsafe SQL fragment detected (comments, multiple statements, and DDL/DML are not allowed).');
            }
        }
    }

    /**
     * Defense-in-depth on top of validateSqlFragment(): restricts which tables/columns a report's
     * sql/from/where/groupby fragments may reference (including references inside subqueries or
     * behind backtick-quoting), independent of the SQL-syntax checks in validateSqlFragment().
     *
     * This is a name-based deny-list, not a schema-aware allow-list: it blocks references by literal
     * table/column name regardless of context, which can also block legitimate reports if a non-sensitive
     * table/column happens to share a denied name. Adjust pimcore_custom_reports.sql_adapter.denied_tables
     * / denied_columns to fit your schema.
     */
    private function validateAgainstDenyList(string $sql): void
    {
        $sqlForValidation = $this->normalizeForDenyListCheck($sql);

        foreach ($this->getDeniedTables() as $table) {
            if ($table === '') {
                continue;
            }

            if (preg_match($this->identifierBoundaryPattern($table), $sqlForValidation)) {
                throw new InvalidArgumentException(sprintf('Access to table "%s" is not permitted in Custom Report SQL.', $table));
            }
        }

        foreach ($this->getDeniedColumns() as $column) {
            if ($column === '') {
                continue;
            }

            if (preg_match($this->identifierBoundaryPattern($column), $sqlForValidation)) {
                throw new InvalidArgumentException(sprintf('Access to column "%s" is not permitted in Custom Report SQL.', $column));
            }
        }
    }

    /**
     * \b relies on \w (ASCII word characters), so it silently fails to anchor around configured names
     * that start/end with characters outside that set - e.g. a denied name of "$private$" is never
     * matched against "... FROM $private$" since neither the "$" nor the preceding space is a \w
     * character, so no \w/non-\w transition ever occurs there. This defines "part of the same
     * identifier" more broadly (MySQL's own unquoted-identifier character set: letters, digits,
     * underscore, dollar sign, plus Unicode) so the boundary check works for identifiers like that too.
     */
    private function identifierBoundaryPattern(string $name): string
    {
        return '/(?<![\p{L}\p{N}_$])' . preg_quote($name, '/') . '(?![\p{L}\p{N}_$])/iu';
    }

    private function normalizeForDenyListCheck(string $sql): string
    {
        // Blank out string literals so denied names appearing only as data values don't trigger false
        // positives. See validateSqlFragment() for why backslash must be excluded from the catch-all
        // and consumed pairwise via \\. rather than treated as an ordinary character.
        $normalized = preg_replace(
            [
                "/'(?:''|\\\\.|[^'\\\\])*'/s",
                '/"(?:""|\\\\.|[^"\\\\])*"/s',
            ],
            ["''", '""'],
            $sql
        ) ?? $sql;

        // Unwrap backtick-quoted identifiers (rather than blanking them, like validateSqlFragment() does)
        // so a denied name can't be hidden from detection behind identifier quoting, e.g. `users`.
        $normalized = preg_replace('/`([^`]*)`/', '$1', $normalized) ?? $normalized;

        return preg_replace('/\s+/s', ' ', $normalized) ?? $normalized;
    }

    /**
     * A "sql" (column list) fragment is allowed to be an entire freeform SELECT statement (it's used
     * as-is if it already starts with "SELECT"), so reliably detecting every way a wildcard projection
     * could end up in the result set (DISTINCT, UNION, subqueries, aliases, ...) via text/regex matching
     * on the fragment isn't tractable - there's always another syntactic variant that slips through.
     *
     * Instead of guessing from the query text, this checks what the query's result columns actually
     * are (see getResolvedColumnNames()) against the deny-list. This is exact-match, not
     * name-matching-anywhere-in-text, since these are now genuine column identifiers rather than free text.
     *
     * @param string[] $columnNames
     */
    private function assertResolvedColumnsAllowed(array $columnNames): void
    {
        $deniedColumns = $this->getDeniedColumns();
        if (!$deniedColumns) {
            return;
        }

        foreach ($columnNames as $column) {
            foreach ($deniedColumns as $denied) {
                if ($denied !== '' && strcasecmp((string) $column, $denied) === 0) {
                    throw new InvalidArgumentException(sprintf('Access to column "%s" is not permitted in Custom Report SQL.', $column));
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    private function assertResolvedColumnsAllowedForQuery(string $sql, Connection $db): void
    {
        if (!$this->getDeniedColumns()) {
            return;
        }

        $this->assertResolvedColumnsAllowed($this->getResolvedColumnNames($sql, $db));
    }

    /**
     * Determines a query's actual result columns via driver-level result metadata
     * (Doctrine\DBAL\Result::columnCount()/getColumnName()) rather than by fetching a sample row.
     * This matters for two reasons: it works even when zero rows match (a non-deterministic predicate
     * or a concurrent data change could otherwise make a row-based sample empty while the real query
     * returns rows later, silently skipping the deny-list check), and wrapping the query as a derived
     * table with "LIMIT 0" - rather than appending "LIMIT 0,1" to $sql directly - keeps it valid even
     * if $sql already ends in its own LIMIT clause, while letting the query planner typically avoid
     * doing the real (potentially expensive) row-producing work "LIMIT 1" would require.
     *
     * @throws Exception
     *
     * @return string[]
     */
    private function getResolvedColumnNames(string $sql, Connection $db): array
    {
        $result = $db->executeQuery('SELECT * FROM (' . $sql . ') AS somerandxyz2 LIMIT 0');

        $columnNames = [];
        for ($i = 0, $count = $result->columnCount(); $i < $count; $i++) {
            $columnNames[] = $result->getColumnName($i);
        }
        $result->free();

        return $columnNames;
    }

    private function getDeniedTables(): array
    {
        return Pimcore::getContainer()->getParameter('pimcore_custom_reports.sql_adapter.denied_tables');
    }

    private function getDeniedColumns(): array
    {
        return Pimcore::getContainer()->getParameter('pimcore_custom_reports.sql_adapter.denied_columns');
    }

    protected function getBaseQuery(array $filters, array $fields, bool $ignoreSelectAndGroupBy = false, ?array $drillDownFilters = null, ?string $selectField = null): ?array
    {
        $db = Db::get();
        $condition = ['1 = 1'];

        $sql = $this->buildQueryString($this->config, $ignoreSelectAndGroupBy, $drillDownFilters, $selectField);

        $extractAllFields = empty($fields);
        foreach ($filters as $filter) {
            $value = $filter['value'] ?? null;
            $type = $filter['type'];
            $operator = $filter['operator'];
            $maxValue = null;
            if ($type == 'date') {
                if ($operator == 'eq') {
                    $maxValue = strtotime($value . '+23 hours 59 minutes');
                }
                $value = strtotime($value);
            }

            switch ($operator) {
                case 'like':
                    $fields[] = $filter['property'];
                    $condition[] = $db->quoteIdentifier($filter['property']) . ' LIKE ' . $db->quote('%' . $value. '%');

                    break;
                case 'lt':
                case 'gt':
                case 'eq':
                    $compMapping = [
                        'lt' => '<',
                        'gt' => '>',
                        'eq' => '=',
                    ];

                    if (($type == 'date') && $operator == 'eq') {
                        $condition[] = $db->quoteIdentifier(
                            $filter['property']) .
                            ' BETWEEN ' .
                            $db->quote($value) .
                            ' AND ' .
                            $db->quote((string)$maxValue);

                        break;
                    }
                    $fields[] = $filter['property'];
                    $condition[] = $db->quoteIdentifier($filter['property']) . ' ' . $compMapping[$operator] . ' ' . $db->quote($value);

                    break;
                case '=':
                    $fields[] = $filter['property'];
                    $condition[] = $db->quoteIdentifier($filter['property']) . ' = ' . $db->quote($value);

                    break;
            }
        }

        $sqlStripped = $this->stripSqlCommentsForValidation($sql);
        if (
            !preg_match('/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE)\s/i', $sqlStripped, $matches)
        ) {
            $this->assertResolvedColumnsAllowedForQuery($sql, $db);

            $condition = implode(' AND ', $condition);

            $total = 'SELECT COUNT(*) FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;

            if ($fields && !$extractAllFields) {
                $quotedFields = array_map(fn ($f) => $db->quoteIdentifier($f), $fields);
                $data = 'SELECT ' . implode(', ', $quotedFields) . ' FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
            } else {
                $data = 'SELECT * FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
            }
        } else {
            return null;
        }

        return [
            'data' => $data,
            'count' => $total,
        ];
    }

    public function getAvailableOptions(array $filters, string $field, array $drillDownFilters): array
    {
        $db = Db::get();
        $baseQuery = $this->getBaseQuery($filters, [$field], false, $drillDownFilters);
        $data = [];
        if ($baseQuery) {
            $sql = $baseQuery['data'] . ' GROUP BY ' . $db->quoteIdentifier($field);
            $data = $db->fetchAllAssociative($sql);
        }

        $filteredData = [];
        foreach ($data as $d) {
            if (!empty($d[$field]) || $d[$field] === 0) {
                $filteredData[] = ['name' => $d[$field], 'value' => $d[$field]];
            }
        }

        return [
            'data' => array_merge(
                [
                    ['name' => 'empty', 'value' => null],
                ],
                $filteredData
            ),
        ];
    }

    private function stripSqlCommentsForValidation(string $sql): string
    {
        $sqlStripped = preg_replace('/\/\*!\d*\s*(.*?)\*\//s', ' $1 ', $sql);
        $sqlStripped = preg_replace('/\/\*(?!\!).*?\*\//s', ' ', $sqlStripped ?? '');
        $sqlStripped = preg_replace('/\s+/', ' ', $sqlStripped ?? '');

        return $sqlStripped;
    }
}
