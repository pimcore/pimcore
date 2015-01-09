<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\User\Workspace;

use Pimcore\Model;
use Pimcore\Model\User\Workspace;

class Resource extends Model\Resource\AbstractResource {

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
