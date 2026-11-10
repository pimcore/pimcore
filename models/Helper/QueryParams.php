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

namespace Pimcore\Model\Helper;

use Carbon\Carbon;
use Exception;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * @internal
 */
class QueryParams
{
    /**
     * @return array{
     *     orderKey: string|null,
     *     order: string|null,
     *     fieldname?: string,
     *     groupId?: int,
     *     keyId?: int,
     *     isFeature?: int,
     *     groupIdentifier?: string,
     *     keyIdentifier?: string
     * }
     */
    public static function extractSortingSettings(array $params, ?int $storeId = null): array
    {
        $orderKey = null;
        $order = null;

        $sortParamRaw = $params['sort'] ?? null;
        if ($sortParamRaw !== null && $sortParamRaw !== '') {
            $decoded = json_decode($sortParamRaw, true);

            if (is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
                $sortParam = $decoded[0];

                $direction = isset($sortParam['direction']) ? strtoupper((string) $sortParam['direction']) : null;
                $order = $direction === 'DESC' ? 'DESC' : 'ASC';

                if (!isset($sortParam['property']) || !is_string($sortParam['property']) || $sortParam['property'] === '') {
                    return ['orderKey' => $orderKey, 'order' => $order];
                }

                if (substr($sortParam['property'], 0, 1) !== '~') {
                    $orderKey = $sortParam['property'];
                } else {
                    $orderKey = $sortParam['property'];

                    $parts = explode('~', $orderKey);
                    // identifiers cannot contain '~', so only four (legacy) or five (identifier) segments are valid
                    if (!in_array(count($parts), [4, 5], true)) {
                        return ['orderKey' => $orderKey, 'order' => $order];
                    }

                    $fieldname = $parts[2];

                    if (count($parts) === 5) {
                        return self::extractIdentifierSortingSettings(
                            $orderKey,
                            $order,
                            $fieldname,
                            $parts[3],
                            $parts[4],
                            $storeId
                        );
                    }

                    $groupKeyId = $parts[3];
                    $groupKeyIdParts = explode('-', $groupKeyId);
                    if (count($groupKeyIdParts) < 2) {
                        return ['orderKey' => $orderKey, 'order' => $order];
                    }

                    $groupId = (int) $groupKeyIdParts[0];
                    $keyid = (int) $groupKeyIdParts[1];

                    return [
                        'orderKey' => $sortParam['property'],
                        'fieldname' => $fieldname,
                        'groupId' => $groupId,
                        'keyId' => $keyid,
                        'order' => $order,
                        'isFeature' => 1,
                    ];
                }
            }
        }

        return ['orderKey' => $orderKey, 'order' => $order];
    }

    /**
     * The five segment token: with a store scope the identifiers are resolved to their ids, without one they are
     * returned as they are and the result carries no isFeature.
     */
    private static function extractIdentifierSortingSettings(
        string $orderKey,
        ?string $order,
        string $fieldname,
        string $groupIdentifier,
        string $keyIdentifier,
        ?int $storeId
    ): array {
        if ($storeId === null) {
            return [
                'orderKey' => $orderKey,
                'order' => $order,
                'groupIdentifier' => $groupIdentifier,
                'keyIdentifier' => $keyIdentifier,
            ];
        }

        $groupConfig = GroupConfig::getByIdentifier($groupIdentifier, $storeId);
        $keyConfig = KeyConfig::getByIdentifier($keyIdentifier, $storeId);

        if ($groupConfig === null || $keyConfig === null) {
            return ['orderKey' => $orderKey, 'order' => $order];
        }

        return [
            'orderKey' => $orderKey,
            'fieldname' => $fieldname,
            'groupId' => $groupConfig->getId(),
            'keyId' => $keyConfig->getId(),
            'order' => $order,
            'isFeature' => 1,
        ];
    }

    public static function getRecordIdForGridRequest(string $param): int
    {
        $decoded = json_decode($param, true);

        if (!is_array($decoded) || !array_key_exists('id', $decoded)) {
            return 0;
        }

        return (int) $decoded['id'];
    }

    /**
     * Creates a condition string from the passed ExtJs filter definitions
     *
     *
     *
     * @throws Exception
     */
    public static function getFilterCondition(string $filterString, array $matchExact = ['id', 'id'], bool $returnString = true, array $callbacks = []): array|string
    {
        if (!$filterString) {
            return '';
        }
        $conditions = [];

        $filters = json_decode($filterString);
        if (!is_array($filters) && !is_object($filters)) {
            return $returnString ? '' : [];
        }

        $db = \Pimcore\Db::get();
        foreach ($filters as $f) {
            if ($f->type == 'string') {
                if (in_array($f->property, $matchExact)) {
                    $conditions[$f->property][] = ' ' . $db->quoteIdentifier($f->property) . ' = ' . $db->quote((string)$f->value) . ' ';
                } else {
                    $conditions[$f->property][] = ' ' . $db->quoteIdentifier($f->property) . ' LIKE ' . $db->quote('%' . $f->value . '%') . ' ';
                }
            } elseif ($f->type == 'numeric') {
                $symbol = ' = ';
                if ($f->operator == 'lt') {
                    $symbol = ' < ';
                } elseif ($f->operator == 'gt') {
                    $symbol = ' > ';
                }
                $conditions[$f->property][] = ' ' . $db->quoteIdentifier($f->property)  . ' ' . $symbol . $db->quote((string)$f->value) . ' ';
            } elseif ($f->type == 'date') {
                /**
                 * make sure you pass the date as timestamp
                 *
                 * filter: {type : 'date',dateFormat: 'timestamp'}
                 */
                $date = Carbon::createFromTimestamp($f->value, date_default_timezone_get())->setTime(0, 0, 0);

                if ($f->operator == 'eq') {
                    $conditions[$f->property][] = ' ' . $db->quoteIdentifier($f->property) . ' >= ' .
                        $db->quote((string)$date->getTimestamp());
                    $conditions[$f->property][] = ' ' . $db->quoteIdentifier($f->property) . ' <= ' .
                        $db->quote((string)$date->addDay()->subSecond()->getTimestamp());
                } elseif ($f->operator == 'lt') {
                    $conditions[$f->property][] = ' ' . $db->quoteIdentifier($f->property) . ' < ' .
                        $db->quote((string)$date->getTimestamp());
                } elseif ($f->operator == 'gt') {
                    $conditions[$f->property][] = ' ' . $db->quoteIdentifier($f->property) . ' > ' .
                        $db->quote((string)$date->addDay()->subSecond()->getTimestamp());
                }
            } else {
                throw new Exception('Filter of type ' . $f->type . ' not yet supported.');
            }
        }

        $conditionsGrouped = [];
        foreach ($conditions as $fieldName => $fieldConditions) {
            if (count($fieldConditions) > 1) {
                $conditionsGrouped[$fieldName] = ' (' . implode(' AND ', $fieldConditions) . ') ';
            } else {
                $conditionsGrouped[$fieldName] = $fieldConditions[0];
            }
        }
        if ($returnString) {
            return implode(' OR ', $conditionsGrouped);
        } else {
            return $conditionsGrouped;
        }
    }
}
