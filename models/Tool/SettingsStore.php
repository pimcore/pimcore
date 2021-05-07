<?php

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

namespace Pimcore\Model\Tool;

use Pimcore\Model;
use Pimcore\Model\Tool\SettingsStore\Dao;

/**
 * @method Dao getDao()
 */
class SettingsStore extends Model\AbstractModel
{
    protected static $allowedTypes = ['bool', 'int', 'float', 'string'];

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $scope;

    /**
     * @var string
     */
    public $type;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var SettingsStore
     */
    protected static $instance;

    /**
     * @return SettingsStore
     */
    protected static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $type
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected static function validateType(string $type): bool
    {
        if (!in_array($type, self::$allowedTypes)) {
            throw new \Exception(sprintf('Invalid type `%s`, allowed types are %s', $type, implode(',', self::$allowedTypes)));
        }

        return true;
    }

    /**
     * @param string $id
     * @param int|string|bool|float $data
     * @param string $type
     * @param string|null $scope
     *
     * @return bool
     *
     * @throws \Exception
     */
    public static function set(string $id, $data, string $type = 'string', ?string $scope = null): bool
    {
        self::validateType($type);
        $instance = self::getInstance();

        return $instance->getDao()->set($id, $data, $type, $scope);
    }

    /**
     * @param string $id
     * @param string|null $scope
     *
     * @return mixed
     */
    public static function delete(string $id, ?string $scope = null)
    {
        $instance = self::getInstance();

        return $instance->getDao()->delete($id, $scope);
    }

    /**
     * @param string $id
     * @param string|null $scope
     *
     * @return SettingsStore|null
     */
    public static function get(string $id, ?string $scope = null): ?SettingsStore
    {
        $item = new self();
        if ($item->getDao()->getById($id, $scope)) {
            return $item;
        }

        return null;
    }

    /**
     * @param string $scope
     *
     * @return string[]
     */
    public static function getIdsByScope(string $scope): array
    {
        $instance = self::getInstance();

        return $instance->getDao()->getIdsByScope($scope);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * @param string|null $scope
     */
    public function setScope(?string $scope): void
    {
        $this->scope = (string) $scope;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @throws \Exception
     */
    public function setType(string $type): void
    {
        self::validateType($type);
        $this->type = $type;
    }

    /**
     * @return int|string|bool|float
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param int|string|bool|float $data
     */
    public function setData($data): void
    {
        if (!empty($this->getType())) {
            settype($data, $this->getType());
        }
        $this->data = $data;
    }
}
