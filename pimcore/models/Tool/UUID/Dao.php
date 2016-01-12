<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Tool\UUID;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    const TABLE_NAME = 'uuids';

    /**
     *
     */
    public function save () {
        $data = get_object_vars($this->model);

        foreach($data as $key => $value){
            if(!in_array($key, $this->getValidTableColumns(static::TABLE_NAME))){
                unset($data[$key]);
            }
        }

        $this->db->insertOrUpdate(self::TABLE_NAME,$data);
    }

    /**
     * @throws \Exception
     */
    public function delete(){
        $uuid = $this->model->getUuid();
        if(!$uuid){
            throw new \Exception("Couldn't delete UUID - no UUID specified.");
        }
        $this->db->delete(self::TABLE_NAME,"uuid='". $uuid ."'");
    }

    /**
     * @param $uuid
     * @return Tool\UUID
     */
    public function getByUuid($uuid){
        $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME ." where uuid='" . $uuid . "'");
        $model = new Model\Tool\UUID();
        $model->setValues($data);
        return $model;
    }
}
