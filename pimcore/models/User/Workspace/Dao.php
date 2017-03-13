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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\Workspace;

use Pimcore\Model;
use Pimcore\Model\User\Workspace;

/**
 * @property \Pimcore\Model\User\Workspace\Object $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     *
     */
    public function save()
    {
        $tableName = "";
        if ($this->model instanceof Workspace\Asset) {
            $tableName = "users_workspaces_asset";
        } elseif ($this->model instanceof Workspace\Document) {
            $tableName = "users_workspaces_document";
        } elseif ($this->model instanceof Workspace\Object) {
            $tableName = "users_workspaces_object";
        }

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
}
