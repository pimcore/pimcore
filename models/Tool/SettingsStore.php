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

namespace Pimcore\Model\Tool;

use Exception;
use Pimcore\Model;
use Pimcore\Model\Tool\SettingsStore\Dao;

/**
 * @method Dao getDao()
 */
final class SettingsStore extends Model\AbstractModel
{
    public const TYPE_BOOLEAN = 'bool';

    public const TYPE_FLOAT = 'float';

    public const TYPE_INTEGER = 'int';

    public const TYPE_STRING = 'string';

    protected const ALLOWED_TYPES = [
        self::TYPE_BOOLEAN,
        self::TYPE_FLOAT,
        self::TYPE_INTEGER,
        self::TYPE_STRING,
    ];

    /**
     * @internal
     */
    protected string $id;

    /**
     * @internal
     */
    protected ?string $scope = null;

    /**
     * @internal
     */
    protected string $type = '';

    /**
     * @internal
     */
    protected mixed $data = null;

    /**
     * @internal
     */
    protected static ?self $instance = null;

    /**
     * Entries of a scope as loaded by {@see self::loadScopeSnapshot()}, by scope and id.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    private static array $scopeSnapshots = [];

    private static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @throws Exception
     */
    private static function validateType(string $type): bool
    {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new Exception(sprintf('Invalid type `%s`, allowed types are %s', $type, implode(',', self::ALLOWED_TYPES)));
        }

        return true;
    }

    /**
     *
     *
     * @throws Exception
     */
    public static function set(string $id, float|bool|int|string $data, string $type = 'string', ?string $scope = null): bool
    {
        self::validateType($type);
        $instance = self::getInstance();
        unset(self::$scopeSnapshots[(string) $scope]);

        return $instance->getDao()->set($id, $data, $type, $scope);
    }

    public static function delete(string $id, ?string $scope = null): int|string
    {
        $instance = self::getInstance();
        unset(self::$scopeSnapshots[(string) $scope]);

        return $instance->getDao()->delete($id, $scope);
    }

    public static function get(string $id, ?string $scope = null): ?SettingsStore
    {
        try {
            $item = new self();
            $item->getDao()->getById($id, $scope);

            return $item;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    /**
     *
     * @return string[]
     */
    public static function getIdsByScope(string $scope): array
    {
        $instance = self::getInstance();

        return $instance->getDao()->getIdsByScope($scope);
    }

    /**
     * Loads every entry of a scope with a single query and keeps the entries in memory,
     * so that {@see self::getFromScopeSnapshot()} can return them without a query each.
     *
     * Meant for callers that list a scope and then read each of its entries, such as the
     * configuration listings of {@see \Pimcore\Config\LocationAwareConfigRepository}.
     * The snapshot of a scope is replaced by the next call for that scope and dropped by
     * {@see self::set()} and {@see self::delete()} on it.
     *
     * @return string[] the ids of the scope, as {@see self::getIdsByScope()} returns them
     *
     * @internal
     */
    public static function loadScopeSnapshot(string $scope): array
    {
        $ids = [];
        $rows = [];
        foreach (self::getInstance()->getDao()->getRowsByScope($scope) as $row) {
            $ids[] = $row['id'];
            $rows[$row['id']] = $row;
        }

        self::$scopeSnapshots[$scope] = $rows;

        return $ids;
    }

    /**
     * Returns an entry from the snapshot taken by {@see self::loadScopeSnapshot()}.
     *
     * Null means that the entry is not in a snapshot - either no snapshot of the scope
     * exists, or the entry did not exist when it was taken. Use {@see self::get()} then.
     *
     * @internal
     */
    public static function getFromScopeSnapshot(string $id, ?string $scope = null): ?SettingsStore
    {
        $row = self::$scopeSnapshots[(string) $scope][$id] ?? null;
        if ($row === null) {
            return null;
        }

        $item = new self();
        $item->getDao()->assignRow($row);

        return $item;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): void
    {
        $this->scope = (string) $scope;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     *
     * @throws Exception
     */
    public function setType(string $type): void
    {
        self::validateType($type);
        $this->type = $type;
    }

    public function getData(): float|bool|int|string
    {
        return $this->data;
    }

    public function setData(float|bool|int|string $data): void
    {
        if (!empty($this->getType())) {
            settype($data, $this->getType());
        }
        $this->data = $data;
    }
}
