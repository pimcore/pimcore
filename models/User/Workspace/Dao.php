<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\User\Workspace;

use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\User\Workspace;

/**
 * @internal
 *
 * @property Workspace\Asset|Workspace\Document|Workspace\DataObject $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function save(): void
    {
        $tableName = '';
        if ($this->model instanceof Workspace\Asset) {
            $tableName = 'users_workspaces_asset';
        } elseif ($this->model instanceof Workspace\Document) {
            $tableName = 'users_workspaces_document';
        } elseif ($this->model instanceof Workspace\DataObject) {
            $tableName = 'users_workspaces_object';
        }

        $data = [];

        // add all permissions
        $dataRaw = $this->model->getObjectVars();
        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $this->getValidTableColumns($tableName))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }
        $this->db->insert($tableName, Helper::quoteDataIdentifiers($this->db, $data));
    }
}
