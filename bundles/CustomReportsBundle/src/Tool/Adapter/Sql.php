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

use Exception;
use InvalidArgumentException;
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
            // Wrap in a derived table (as getBaseQuery() does) rather than appending a raw
            // ' LIMIT 0,1' to the concatenated string: a statement-terminating primitive such
            // as INTO OUTFILE is a syntax error inside the subquery, whereas appending LIMIT
            // directly is only a string suffix and can be neutralised by a trailing comment.
            $wrappedSql = 'SELECT * FROM (' . $sql . ') AS somerandxyz LIMIT 0,1';
            $res = $this->fetchAssociative($wrappedSql);
            if ($res) {
                return array_keys($res);
            }

            return [];
        }

        throw new Exception("Only 'SELECT' statements are allowed! You've used '" . $matches[0] . "'");
    }

    /**
     * Thin seam around the actual DB round-trip, so tests can assert on the exact
     * (already-wrapped) SQL getColumns() sends without needing a real DB connection.
     */
    protected function fetchAssociative(string $sql): array|false
    {
        return Db::get()->fetchAssociative($sql);
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
        //
        // The backslash alternative must be tried before the catch-all "any non-quote char" one:
        // MySQL's default (non-NO_BACKSLASH_ESCAPES) lexer treats a backslash as escaping exactly
        // the next character, so "\\" is one escaped backslash and the following quote closes the
        // string. Matching "\\." first consumes both bytes of that escape as a unit; matching the
        // backslash on its own via "[^']" first (as this used to) leaves the second backslash to
        // combine with the real closing quote into a bogus "escaped quote", desynchronizing this
        // regex from MySQL's lexer and letting it scan past the string's actual end.
        $sqlForValidation = preg_replace(
            [
                "/'(?:\\\\.|''|[^'])*'/s",
                '/"(?:\\\\.|""|[^"])*"/s',
                '/`[^`]*`/s',
            ],
            ["''", '""', '``'],
            $sql
        ) ?? $sql;

        // Normalize whitespace/newlines for consistent boundary checking
        $sqlForValidation = preg_replace('/\s+/s', ' ', $sqlForValidation) ?? $sqlForValidation;
        $forbiddenPatterns = [
            '/;/',
            // Comment start: MySQL's lexer treats "--" as a comment when followed by a space
            // or *any* control character (my_iscntrl), not only the whitespace \s matches, or
            // when "--" ends the fragment outright.
            '/--(?:[\x00-\x20\x7F]|$)/',
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
            '/\bINTO\s+OUTFILE\b/i',
            '/\bINTO\s+DUMPFILE\b/i',
            '/\bLOAD_FILE\s*\(/i',
        ];

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $sqlForValidation)) {
                throw new InvalidArgumentException('Unsafe SQL fragment detected (comments, multiple statements, DDL/DML, and file access functions are not allowed).');
            }
        }
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

            $value = (string) $value;

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
