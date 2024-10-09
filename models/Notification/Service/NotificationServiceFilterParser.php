<?php

declare(strict_types=1);

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

namespace Pimcore\Model\Notification\Service;

use Carbon\Carbon;
use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class NotificationServiceFilterParser
{
    const KEY_FILTER = 'filter';

    const KEY_TYPE = 'type';

    const KEY_PROPERTY = 'property';

    const KEY_OPERATOR = 'operator';

    const KEY_VALUE = 'value';

    const TYPE_STRING = 'string';

    const TYPE_DATE = 'date';

    const OPERATOR_LIKE = 'like';

    const OPERATOR_EQ = 'eq';

    const OPERATOR_GT = 'gt';

    const OPERATOR_LT = 'lt';

    private Request $request;

    private array $properties = [
        'title' => 'title',
        'timestamp' => 'creationDate',
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return array<string, array{condition: string, conditionVariables: array<string, mixed>}>
     */
    public function parse(): array
    {
        $result = [];
        $filter = $this->request->request->get(self::KEY_FILTER, '[]');
        $items = json_decode($filter, true);

        foreach ($items as $item) {
            $type = $item[self::KEY_TYPE];

            switch ($type) {
                case self::TYPE_STRING:
                    [$key, $condition, $conditionVariables] = $this->parseString($item);
                    $result[$key] = [
                        'condition' => $condition,
                        'conditionVariables' => $conditionVariables,
                    ];

                    break;
                case self::TYPE_DATE:
                    [$key, $condition, $conditionVariables] = $this->parseDate($item);
                    $result[$key] = [
                        'condition' => $condition,
                        'conditionVariables' => $conditionVariables,
                    ];

                    break;
            }
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: string, 2: array<string, mixed>}
     *
     * @throws Exception
     */
    private function parseString(array $item): array
    {
        $result = null;
        $property = $this->getDbProperty($item);
        $value = $item[self::KEY_VALUE] ?? '';

        switch ($item[self::KEY_OPERATOR]) {
            case self::OPERATOR_LIKE:
                $key = $property . '_like';
                $result = [
                    $key,
                    "{$property} LIKE :{$key}",
                    [$key => "%{$value}%"],
                ];

                break;
        }

        if (is_null($result)) {
            throw new Exception();
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: string, 2: array<string, mixed>}
     *
     * @throws Exception
     */
    private function parseDate(array $item): array
    {
        $result = null;
        $property = $this->getDbProperty($item);
        $value = new Carbon($item[self::KEY_VALUE]);

        switch ($item[self::KEY_OPERATOR]) {
            case self::OPERATOR_EQ:
                $key = $property . '_eq';
                $result = [
                    $key,
                    "{$property} BETWEEN :{$key}_start AND :{$key}_end",
                    [
                        $key . '_start' => $value->toDateTimeString(),
                        $key . '_end' => $value->addDay()->subSecond()->toDateTimeString(),
                    ],
                ];

                break;
            case self::OPERATOR_GT:
                $key = $property . '_gt';
                $result = [
                    $key,
                    "{$property} > :{$key}",
                    [$key => $value->toDateTimeString()],
                ];

                break;
            case self::OPERATOR_LT:
                $key = $property . '_lt';
                $result = [
                    $key,
                    "{$property} < :{$key}",
                    [$key => $value->addDay()->subSecond()->toDateTimeString()],
                ];

                break;
        }

        if (is_null($result)) {
            throw new Exception();
        }

        return $result;
    }

    private function getDbProperty(array $item): string
    {
        $property = $item[self::KEY_PROPERTY];

        return isset($this->properties[$property]) ? $this->properties[$property] : $property;
    }
}
