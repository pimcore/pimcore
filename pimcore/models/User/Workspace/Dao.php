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
 * @category   Pimcore
 * @package    User
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\Workspace;

use Pimcore\Model;
use Pimcore\Model\User\Workspace;

/**
 * @property \Pimcore\Model\User\Workspace\DataObject $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $tableName = $this->getTableName();

        $data = [];

        // add all permissions
        $dataRaw = get_object_vars($this->model);
        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $this->getValidTableColumns($tableName))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }
        $this->db->insert($tableName, $data);
    }

    /**
     * Get the data for the object from database for the given path
     *
     * @param   string      $path   workspace c-path
     *
     * @throws \Exception   if no entry was found
     */
    public function getByPath($path)
    {
        if (!($tableName = $this->getTableName())) {
            // should only happen while developing
            throw new \RuntimeException("No Table found for Model '" . get_class($this) . '"');
        }

        $data = $this->db->fetchRow('SELECT cid FROM '. $tableName . ' WHERE cpath = ' . $this->db->quote($path));

        if ($data['cid']) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Workspace doesn't exist");
        }
    }

    /**
     * Get the data for the workspace from database for the given id
     *
     * @param   int         $id workspace id
     *
     * @throws \Exception   if no entry was found
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT * FROM " . $this->getTableName() . " WHERE cid = ?", $id);

        if ($data['cid']) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Object with the ID $id doesn't exists");
        }
    }

    /**
     * get table of workspace
     *
     * @return  string  table name
     */
    protected function getTableName()
    {
        $tableName = '';
        if ($this->model instanceof Workspace\Asset) {
            $tableName = 'users_workspaces_asset';
        } elseif ($this->model instanceof Workspace\Document) {
            $tableName = 'users_workspaces_document';
        } elseif ($this->model instanceof Workspace\DataObject) {
            $tableName = 'users_workspaces_object';
        }

        return $tableName;
    }
}
