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

namespace Pimcore\Model\Listing;

use Countable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use InvalidArgumentException;
use Iterator;
use Pimcore\Db;
use Pimcore\Db\Helper;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Listing\Dao\AbstractDao;

/**
 * @method AbstractDao getDao()
 * @method QueryBuilder getQueryBuilder()
 */
abstract class AbstractListing extends AbstractModel implements Iterator, Countable
{
    protected array $order = [];

    protected array $orderKey = [];

    protected ?int $limit = null;

    protected int $offset = 0;

    protected ?string $condition = null;

    protected array $conditionVariables = [];

    protected ?array $conditionVariablesFromSetCondition = null;

    protected ?string $groupBy = null;

    protected array $validOrders = [
        'ASC',
        'DESC',
    ];

    protected array $conditionParams = [];

    protected array $conditionVariableTypes = [];

    protected ?array $data = null;

    public function getConditionVariableTypes(): array
    {
        if (!$this->conditionVariables) {
            $this->getCondition();
        }

        return $this->conditionVariableTypes;
    }

    public function setConditionVariableTypes(array $conditionVariableTypes): void
    {
        $this->conditionVariableTypes = $conditionVariableTypes;
    }

    public function isValidOrderKey(string $key): bool
    {
        return true;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * @return $this
     */
    public function setLimit(?int $limit): static
    {
        $this->setData(null);

        if (is_numeric($limit)) {
            $this->limit = (int)$limit;
        } else {
            $this->limit = null;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setOffset(int $offset): static
    {
        $this->setData(null);

        $this->offset = $offset;

        return $this;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException If the order is invalid
     */
    public function setOrder(array|string $order): static
    {
        $this->setData(null);

        $this->order = [];

        if (is_string($order)) {
            $order = $order ? [$order] : [];
        }

        foreach ($order as $o) {
            $o = strtoupper($o);
            if (in_array($o, $this->validOrders)) {
                $this->order[] = $o;
            } else {
                throw new InvalidArgumentException('Invalid order: ' . $o);
            }
        }

        return $this;
    }

    public function getOrderKey(): array
    {
        return $this->orderKey;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException If the order key is invalid
     */
    public function setOrderKey(array|string $orderKey, bool $quote = true): static
    {
        $this->setData(null);

        $this->orderKey = [];

        if (is_string($orderKey)) {
            $orderKey = $orderKey ? [$orderKey] : [];
        }

        foreach ($orderKey as $o) {
            if ($quote === false) {
                $this->orderKey[] = $o;
            } elseif ($this->isValidOrderKey($o)) {
                $this->orderKey[] = $this->quoteIdentifier($o);
            } else {
                throw new InvalidArgumentException('Invalid order key: ' . $o);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function addConditionParam(string $condition, mixed $value = null, string $concatenator = 'AND'): static
    {
        $this->setData(null);

        $condition = '('.$condition.')';
        $ignoreParameter = true;

        $conditionWithoutQuotedStrings = preg_replace('/((?<![\\\\])[\'\"])((?:.(?!(?<![\\\\])\\1))*.?)\\1/', '', $condition);
        if (str_contains($conditionWithoutQuotedStrings, '?') || str_contains($conditionWithoutQuotedStrings, ':')) {
            $ignoreParameter = false;
        }
        $this->conditionParams[$condition] = [
            'value' => $value,
            'concatenator' => $concatenator,
            'ignore-value' => $ignoreParameter, // If there is not a placeholder, ignore value!
        ];

        return $this;
    }

    public function getConditionParams(): array
    {
        return $this->conditionParams;
    }

    /**
     * @return $this
     */
    public function resetConditionParams(): static
    {
        $this->setData(null);

        $this->conditionParams = [];

        return $this;
    }

    public function getCondition(): string
    {
        $conditionString = '';
        $conditionVariableTypes = [];
        $conditionParams = $this->getConditionParams();

        $params = [];
        if (!empty($conditionParams)) {
            $i = 0;
            foreach ($conditionParams as $key => $value) {
                if (!$this->condition && $i == 0) {
                    $conditionString .= $key . ' ';
                } else {
                    $conditionString .= ' ' . $value['concatenator'] . ' ' . $key . ' ';
                }

                // If there is not a placeholder, ignore value!
                if (!$value['ignore-value']) {
                    if (is_array($value['value'])) {
                        foreach ($value['value'] as $k => $v) {
                            if (is_int($k)) {
                                $params[] = $v;
                            } else {
                                $params[$k] = $v;
                            }
                        }
                    } else {
                        $params[] = $value['value'];
                    }
                }
                $i++;
            }
        }
        $params = array_merge((array) $this->getConditionVariablesFromSetCondition(), $params);

        $this->setConditionVariables($params);

        foreach ($params as $pkey => $param) {
            if (is_array($param)) {
                if (isset($param[0]) && is_string($param[0])) {
                    $conditionVariableTypes[$pkey] = ArrayParameterType::STRING;
                } else {
                    $conditionVariableTypes[$pkey] = ArrayParameterType::INTEGER;
                }
            } else {
                if (is_bool($param)) {
                    $type = ParameterType::BOOLEAN;
                } elseif (is_int($param)) {
                    $type = ParameterType::INTEGER;
                } elseif (is_null($param)) {
                    $type = ParameterType::NULL;
                } else {
                    $type = ParameterType::STRING;
                }

                $conditionVariableTypes[$pkey] = $type;
            }
        }

        $this->setConditionVariableTypes($conditionVariableTypes);

        return $this->condition . $conditionString;
    }

    /**
     * @param array|scalar|null $conditionVariables
     *
     * @return $this
     */
    public function setCondition(string $condition, float|array|bool|int|string $conditionVariables = null): static
    {
        $this->setData(null);

        $this->condition = $condition;

        // statement variables
        if (is_array($conditionVariables)) {
            $this->setConditionVariablesFromSetCondition($conditionVariables);
        } elseif ($conditionVariables !== null) {
            $this->setConditionVariablesFromSetCondition([$conditionVariables]);
        }

        return $this;
    }

    public function getGroupBy(): ?string
    {
        return $this->groupBy;
    }

    public function getValidOrders(): array
    {
        return $this->validOrders;
    }

    /**
     * @return $this
     */
    public function setGroupBy(string $groupBy, bool $qoute = true): static
    {
        $this->setData(null);

        if ($groupBy) {
            $this->groupBy = $groupBy;

            if ($qoute) {
                $quotedParts = [];
                $parts = explode(',', trim($groupBy, '`'));
                foreach ($parts as $part) {
                    $quotedParts[] = $this->quoteIdentifier(trim($part));
                }

                $this->groupBy = implode(', ', $quotedParts);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setValidOrders(array $validOrders): static
    {
        $this->validOrders = $validOrders;

        return $this;
    }

    public function quoteIdentifier(string $value): string
    {
        $db = Db::get();

        return $db->quoteIdentifier($value);
    }

    public function quote(mixed $value, int $type = null): string
    {
        $db = Db::get();

        return $db->quote($value, $type);
    }

    public function escapeLike(string $value): string
    {
        return Helper::escapeLike($value);
    }

    /**
     * @return $this
     */
    public function setConditionVariables(array $conditionVariables): static
    {
        $this->conditionVariables = $conditionVariables;

        return $this;
    }

    public function getConditionVariables(): array
    {
        if (!$this->conditionVariables) {
            $this->getCondition();
        }

        return $this->conditionVariables;
    }

    /**
     * @return $this
     */
    public function setConditionVariablesFromSetCondition(array $conditionVariables): static
    {
        $this->setData(null);

        $this->conditionVariablesFromSetCondition = $conditionVariables;

        return $this;
    }

    public function getConditionVariablesFromSetCondition(): ?array
    {
        return $this->conditionVariablesFromSetCondition;
    }

    public function isLoaded(): bool
    {
        return $this->data !== null;
    }

    public function getData(): ?array
    {
        if ($this->data === null) {
            $this->getDao()->load();
        }

        return $this->data;
    }

    /**
     * @return $this
     */
    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function current(): mixed
    {
        $this->getData();

        return current($this->data);
    }

    public function key(): int|string|null
    {
        $this->getData();

        return key($this->data);
    }

    public function next(): void
    {
        $this->getData();
        next($this->data);
    }

    public function valid(): bool
    {
        $this->getData();

        return $this->current() !== false;
    }

    public function rewind(): void
    {
        $this->getData();
        reset($this->data);
    }

    public function count(): int
    {
        return $this->getDao()->getTotalCount();
    }
}
