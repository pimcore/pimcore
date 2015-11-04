<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User\Workspace;

use Pimcore\Model;
use Pimcore\Model\User\Workspace;

class Dao extends Model\Dao\AbstractDao {

    /**
     *
     */
    public function save () {

        $tableName = "";
        if($this->model instanceof Workspace\Asset) {
            $tableName = "users_workspaces_asset";
        } else if($this->model instanceof Workspace\Document) {
            $tableName = "users_workspaces_document";
        } else if($this->model instanceof Workspace\Object) {
            $tableName = "users_workspaces_object";
        }

        $data = array();

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
