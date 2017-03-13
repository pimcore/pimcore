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

namespace Pimcore\Db;

use Pimcore\File;

class PhpArrayFileTable
{
    /**
     * @var array
     */
    protected static $tables = [];

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var int
     */
    protected $lastInsertId;

    /**
     * @param $filePath
     * @return self
     */
    public static function get($filePath)
    {
        if (!isset(self::$tables[$filePath])) {
            self::$tables[$filePath] = new self($filePath);
        }

        return self::$tables[$filePath];
    }

    /**
     * PhpArrayFileTable constructor.
     * @param string $filePath
     */
    public function __construct($filePath = null)
    {
        if ($filePath) {
            $this->setFilePath($filePath);
        }
    }

    /**
     * @param $filePath
     * @throws \Exception
     */
    public function setFilePath($filePath)
    {
        $writeable = false;

        if (file_exists($filePath) && is_writeable($filePath)) {
            $writeable = true;
        } elseif (!file_exists($filePath)) {
            if (is_writeable(dirname($filePath))) {
                $writeable = true;
            }
        }

        if ($writeable) {
            $this->filePath = $filePath;

            $this->load();
        } else {
            throw new \Exception($filePath . " is not writeable");
        }
    }

    /**
     * @param $data
     * @param string|int $id
     * @throws \Exception
     */
    public function insertOrUpdate($data, $id = null)
    {
        if (!$id) {
            $id = $this->getNextId();
        }

        $data["id"] = $id;
        $this->data[$id] = $data;

        $this->save();
        $this->lastInsertId = $id;
    }

    /**
     * @param string|int $id
     */
    public function delete($id)
    {
        if (isset($this->data[$id])) {
            unset($this->data[$id]);
            $this->save();
        }
    }

    /**
     * @param string|int $id
     * @return array|null
     */
    public function getById($id)
    {
        if (isset($this->data[$id])) {
            return $this->data[$id];
        }

        return null;
    }

    /**
     * @param null $filter
     * @param null $order
     * @return array
     */
    public function fetchAll($filter = null, $order = null)
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

    /**
     * @return int
     */
    public function getNextId()
    {
        $ids = array_keys($this->data);
        if (count($ids)) {
            $id = max($ids) + 1;

            return $id;
        }

        return 1;
    }

    /**
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     *
     */
    public function truncate()
    {
        $this->data = [];
        $this->save();
    }

    /**
     *
     */
    protected function load()
    {
        if (file_exists($this->filePath)) {
            $this->data = include($this->filePath);
            if (!is_array($this->data)) {
                $this->data = [];
            }
        }
    }

    /**
     *
     */
    protected function save()
    {
        $contents = to_php_data_file_format($this->data);
        File::putPhpFile($this->filePath, $contents);
    }
}
