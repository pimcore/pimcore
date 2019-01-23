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

declare(strict_types=1);

namespace Pimcore\Model\Notification\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class ExtJSFilterParser
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

    /**
     * @var Request
     */
    private $request;

    /**
     * @var array
     */
    private $properties;

    /**
     * ExtJSFilterParser constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->properties = [
            'title' => 'title',
            'date' => 'creationDate',
        ];
    }

    /**
     * @return array
     */
    public function parse(): array
    {
        $result = [];
        $filter = $this->request->get(self::KEY_FILTER, '[]');
        $items = json_decode($filter, true);

        foreach ($items as $item) {
            $type = $item[self::KEY_TYPE];

            switch ($type) {
                case self::TYPE_STRING:
                    list($key, $value) = $this->parseString($item);
                    $result[$key] = $value;
                    break;
                case self::TYPE_DATE:
                    list($key, $value) = $this->parseDate($item);
                    $result[$key] = $value;
                    break;
            }
        }

        return $result;
    }

    /**
     * @param array $item
     *
     * @return array
     *
     * @throws \Exception
     */
    private function parseString(array $item): array
    {
        $result = null;
        $property = $this->getDbProperty($item);
        $value = $item[self::KEY_VALUE];

        switch ($item[self::KEY_OPERATOR]) {
            case self::OPERATOR_LIKE:
                $result = ["{$property} LIKE ?", ["%{$value}%"]];
                break;
        }

        if (is_null($result)) {
            throw new \Exception();
        }

        return $result;
    }

    /**
     * @param array $item
     *
     * @return array
     *
     * @throws \Exception
     */
    private function parseDate(array $item): array
    {
        $result = null;
        $property = $this->getDbProperty($item);
        $value = strtotime($item[self::KEY_VALUE]);

        switch ($item[self::KEY_OPERATOR]) {
            case self::OPERATOR_EQ:
                $result = ["{$property} BETWEEN ? AND ?", [$value, $value + (86400 - 1)]];
                break;
            case self::OPERATOR_GT:
                $result = ["{$property} > ?", [$value]];
                break;
            case self::OPERATOR_LT:
                $result = ["{$property} < ?", [$value]];
                break;
        }

        if (is_null($result)) {
            throw new \Exception();
        }

        return $result;
    }

    /**
     * @param array $item
     *
     * @return string
     */
    private function getDbProperty(array $item): string
    {
        $property = $item[self::KEY_PROPERTY];

        return isset($this->properties[$property]) ? $this->properties[$property] : $property;
    }
}
