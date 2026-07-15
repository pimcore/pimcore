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

        $this->assertCompatibleSqlMode();

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
        // Remove quoted strings/identifiers to avoid false positives (e.g. INSERT() function, literals
        // containing "--", "#", ";", etc.) - see stripQuotedTokens() for why this must be one combined
        // pass rather than an independent pass per quote style.
        $sqlForValidation = $this->stripQuotedTokens($sql);

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
            // UNION takes its result column *names* from the first SELECT only (MySQL, and SQL in
            // general), regardless of what a later branch actually selects. A later branch's "SELECT *"
            // against a sensitive table would smuggle its column values out under the first branch's
            // (innocuous-looking) column names - invisible to both this text scan and the resolved-column
            // check, since neither ever sees the later branch's true column identities. Not worth trying
            // to parse around; UNION just isn't supported in Custom Report SQL fragments.
            '/\bUNION\b/i',
        ];

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $sqlForValidation)) {
                throw new InvalidArgumentException('Unsafe SQL fragment detected (comments, multiple statements, UNION, and DDL/DML are not allowed).');
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
        [$sqlForValidation, $quotedIdentifiers] = $this->tokenizeForDenyListCheck($sql);

        foreach ($this->getDeniedTables() as $table) {
            if ($table === '') {
                continue;
            }

            if ($this->identifierListContains($quotedIdentifiers, $table)
                || preg_match($this->identifierBoundaryPattern($table), $sqlForValidation)
            ) {
                throw new InvalidArgumentException(sprintf('Access to table "%s" is not permitted in Custom Report SQL.', $table));
            }
        }

        foreach ($this->getDeniedColumns() as $column) {
            if ($column === '') {
                continue;
            }

            if ($this->identifierListContains($quotedIdentifiers, $column)
                || preg_match($this->identifierBoundaryPattern($column), $sqlForValidation)
            ) {
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
     *
     * This is only valid for *unquoted* SQL text - see tokenizeForDenyListCheck() for why backtick-quoted
     * identifiers must never be scanned with this pattern.
     */
    private function identifierBoundaryPattern(string $name): string
    {
        return '/(?<![\p{L}\p{N}_$])' . preg_quote($name, '/') . '(?![\p{L}\p{N}_$])/iu';
    }

    private function identifierListContains(array $identifiers, string $needle): bool
    {
        foreach ($identifiers as $identifier) {
            if (mb_strtolower($identifier, 'UTF-8') === mb_strtolower($needle, 'UTF-8')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Splits $sql into (a) text with every quoted span - string literals *and* backtick-quoted
     * identifiers - replaced by an inert placeholder, safe to scan with identifierBoundaryPattern() for
     * *unquoted* references, and (b) the list of backtick-quoted identifiers' actual decoded names, to
     * be compared against the deny-list as a whole via identifierListContains() rather than re-scanned
     * as free text.
     *
     * This split matters because a backtick-quoted identifier can legally contain characters - e.g. the
     * hyphen in `reset-password` - that aren't valid in an *unquoted* identifier and so aren't included
     * in identifierBoundaryPattern()'s boundary class. Unwrapping such an identifier back into the
     * general text and scanning it with that pattern loses track of where the identifier actually
     * starts/ends, so "password" would incorrectly match inside the unrelated "reset-password" - a false
     * positive that rejects a legitimate, differently-named column.
     *
     * A doubled backtick ("``") is decoded to one literal backtick when extracting an identifier's real
     * name, matching MySQL's own escape rule for backtick-quoted identifiers. Backslash-escaped
     * characters in string literals are consumed as an atomic pair (\\.) regardless of how many
     * backslashes precede a quote, matching how MySQL itself pairs them up (a single-off "exclude only
     * the quote" version mismatches on an even number of backslashes and can misjudge where a literal
     * actually closes). Single-quoted, double-quoted, and backtick-quoted spans are scanned in one
     * combined left-to-right pass rather than one independent pass per quote style, since scanning
     * styles independently lets a quote character that only means something *inside* one style's
     * literal - e.g. the apostrophe in a double-quoted "it's" - be misread by a later, unrelated pass as
     * the start of its own literal, extending that match across live SQL and hiding it from validation.
     *
     * @return array{0: string, 1: string[]}
     */
    private function tokenizeForDenyListCheck(string $sql): array
    {
        $quotedIdentifiers = [];

        $text = preg_replace_callback(
            '/\'(?:\'\'|\\\\.|[^\'\\\\])*\'|"(?:""|\\\\.|[^"\\\\])*"|`(?:``|[^`])*`/s',
            static function (array $match) use (&$quotedIdentifiers): string {
                $token = $match[0];
                $quote = $token[0];

                if ($quote === '`') {
                    $quotedIdentifiers[] = str_replace('``', '`', substr($token, 1, -1));
                }

                return $quote . $quote;
            },
            $sql
        ) ?? $sql;

        $text = preg_replace('/\s+/s', ' ', $text) ?? $text;

        return [$text, $quotedIdentifiers];
    }

    /**
     * Single left-to-right pass blanking every quoted span (single-quoted/double-quoted string literals
     * and backtick-quoted identifiers) in one combined regex, rather than one independent preg_replace()
     * pass per quote style - see tokenizeForDenyListCheck() for why an independent-pass approach is
     * unsafe. Used ahead of the syntax blacklist in validateSqlFragment(), where (unlike
     * tokenizeForDenyListCheck()) identifier content never needs to be inspected, only kept from
     * producing false hits against the blacklist (e.g. a backtick-quoted column legitimately named
     * `a;b`).
     */
    private function stripQuotedTokens(string $sql): string
    {
        return preg_replace_callback(
            '/\'(?:\'\'|\\\\.|[^\'\\\\])*\'|"(?:""|\\\\.|[^"\\\\])*"|`(?:``|[^`])*`/s',
            static fn (array $match): string => $match[0][0] . $match[0][0],
            $sql
        ) ?? $sql;
    }

    /**
     * A "sql" (column list) fragment is allowed to be an entire freeform SELECT statement (it's used
     * as-is if it already starts with "SELECT"), so reliably detecting every way a wildcard projection
     * could end up in the result set (DISTINCT, subqueries, aliases, ...) via text/regex matching on the
     * fragment isn't tractable - there's always another syntactic variant that slips through. (UNION is
     * rejected outright in validateSqlFragment() rather than handled here: its result columns take their
     * *names* from the first branch only, so a later branch's wildcard could smuggle out a denied
     * column's values under an innocuous name that neither this check nor the text scan ever sees.)
     *
     * Instead of guessing from the query text, this checks what the query's result columns actually
     * are (see getResolvedColumnNames()) against the deny-list. This is exact-match, not
     * name-matching-anywhere-in-text, since these are now genuine column identifiers rather than free
     * text - compared case-insensitively via mb_strtolower() rather than strcasecmp(), which is
     * ASCII-only and would miss a match like "über" vs "ÜBER".
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
                if ($denied !== '' && mb_strtolower((string) $column, 'UTF-8') === mb_strtolower($denied, 'UTF-8')) {
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
     * Determines a query's actual result columns without depending on whether the query itself
     * matches any rows: a non-deterministic predicate or a concurrent data change could otherwise
     * make a plain row-based sample come back empty while the real query returns rows (and a denied
     * column) later, silently skipping the deny-list check. Left-joining $sql against a guaranteed
     * single-row anchor - rather than fetching a row from it directly - always yields exactly one
     * output row, with $sql's own columns present (NULL-valued if $sql itself matched nothing), so
     * their real names can be read via array_keys() regardless of row existence. Wrapping $sql as a
     * derived table also keeps this valid even if $sql already ends in its own LIMIT clause.
     *
     * (Doctrine\DBAL\Result::columnCount()/getColumnName() would be a more direct way to ask this,
     * but aren't available on the floor of this project's supported dbal version range.)
     *
     * @throws Exception
     *
     * @return string[]
     */
    private function getResolvedColumnNames(string $sql, Connection $db): array
    {
        // The join condition is a constant "1 = 0" - provably false independent of $sql's actual rows -
        // rather than "1 = 1". A derived table's column *names* come from its static SELECT-list
        // structure, not from evaluating it, so this still yields exactly one anchor row with $sql's
        // columns present (NULL-valued) either way; but a provably-unsatisfiable condition lets the
        // optimizer skip materializing $sql at all, whereas "1 = 1" (every anchor row matches every
        // $sql row) still requires producing $sql's actual result to pair against.
        $probe = 'SELECT resolved_columns_.*'
            . ' FROM (SELECT 1) AS resolved_columns_anchor_'
            . ' LEFT JOIN (' . $sql . ') AS resolved_columns_ ON 1 = 0'
            . ' LIMIT 1';

        $row = $db->fetchAssociative($probe);

        return $row === false ? [] : array_keys($row);
    }

    private function getDeniedTables(): array
    {
        return Pimcore::getContainer()->getParameter('pimcore_custom_reports.sql_adapter.denied_tables');
    }

    private function getDeniedColumns(): array
    {
        return Pimcore::getContainer()->getParameter('pimcore_custom_reports.sql_adapter.denied_columns');
    }

    /**
     * The deny-list's guarantees rest on this class's tokenizer assuming MySQL's default sql_mode: that
     * a double-quoted span is a string literal (not a quoted identifier, as it would be under
     * ANSI_QUOTES) and that a backslash inside a string literal is an escape character (not literal, as
     * it would be under NO_BACKSLASH_ESCAPES). If the connection's sql_mode doesn't match those
     * assumptions, tokenizeForDenyListCheck()/validateSqlFragment() would tokenize the SQL differently
     * than the server actually parses it, potentially missing a denied reference entirely. Fail closed
     * (reject the report) rather than silently relying on assumptions that don't hold for this
     * connection - this is only checked when a deny-list is actually configured.
     *
     * @throws Exception
     */
    private function assertCompatibleSqlMode(): void
    {
        if (!$this->getDeniedTables() && !$this->getDeniedColumns()) {
            return;
        }

        $sqlMode = (string) Db::get()->fetchOne('SELECT @@SESSION.sql_mode');

        foreach (['ANSI_QUOTES', 'NO_BACKSLASH_ESCAPES'] as $incompatibleMode) {
            if (preg_match('/\b' . $incompatibleMode . '\b/i', $sqlMode)) {
                throw new InvalidArgumentException(sprintf(
                    'The database connection\'s sql_mode includes "%s", which is incompatible with the configured Custom Reports SQL deny-list (pimcore_custom_reports.sql_adapter.denied_tables/denied_columns).',
                    $incompatibleMode
                ));
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
