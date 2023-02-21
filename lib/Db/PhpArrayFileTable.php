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

namespace Pimcore\Db;

use Pimcore\File;

/**
 * @internal
 *
 * @deprecated will be removed in Pimcore 11
 */
final class PhpArrayFileTable
{
    protected static array $tables = [];

    protected string $filePath;

    protected array $data = [];

    protected int $lastInsertId;

    public static function get(string $filePath): PhpArrayFileTable
    {
        if (!isset(self::$tables[$filePath])) {
            self::$tables[$filePath] = new self($filePath);
        }

        return self::$tables[$filePath];
    }

    /**
     * PhpArrayFileTable constructor.
     *
     * @param string|null $filePath
     */
    public function __construct(string $filePath = null)
    {
        if ($filePath) {
            $this->setFilePath($filePath);
        }
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
        $this->load();
    }

    /**
     * @param array $data
     * @param int|string|null $id
     *
     * @throws \Exception
     */
    public function insertOrUpdate(array $data, int|string $id = null): void
    {
        if (!$id) {
            $id = $this->getNextId();
        }

        $data['id'] = $id;
        $this->data[$id] = $data;

        $this->save();
        $this->lastInsertId = $id;
    }

    public function delete(int|string $id): void
    {
        if (isset($this->data[$id])) {
            unset($this->data[$id]);
            $this->save();
        }
    }

    public function getById(int|string $id): ?array
    {
        if (isset($this->data[$id])) {
            return $this->data[$id];
        }

        return null;
    }

    /**
     * @param callable|null $filter
     * @param callable|null $order
     *
     * @return array
     */
    public function fetchAll(callable $filter = null, callable $order = null): array
    {
        $data = $this->data;

        if (is_callable($filter)) {
            $filteredData = [];
            foreach ($data as $row) {
                if ($filter($row)) {
                    $filteredData[] = $row;
                }
            }

            $data = $filteredData;
        }

        if (is_callable($order)) {
            usort($data, $order);
        }

        return $data;
    }

    public function getNextId(): int
    {
        $ids = array_keys($this->data);
        if (count($ids)) {
            $id = max($ids) + 1;

            return $id;
        }

        return 1;
    }

    public function getLastInsertId(): int
    {
        return $this->lastInsertId;
    }

    public function truncate(): void
    {
        $this->data = [];
        $this->save();
    }

    protected function load(): void
    {
        if (file_exists($this->filePath)) {
            $this->data = include($this->filePath);
            if (!is_array($this->data)) {
                $this->data = [];
            }
        }
    }

    protected function save(): void
    {
        $contents = to_php_data_file_format($this->data);
        File::putPhpFile($this->filePath, $contents);
    }
}
