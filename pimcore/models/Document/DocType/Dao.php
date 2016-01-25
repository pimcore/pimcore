<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document\DocType;

use Pimcore\Model;

class Dao extends Model\Dao\PhpArrayTable {

    /**
     *
     */
    public function configure()
    {
        parent::configure();
        $this->setFile("document-types");
    }

    /**
     * Get the data for the object from database for the given id
     * @param null $id
     * @throws \Exception
     */
    public function getById($id = null) {

        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->getById($this->model->getId());
        if(isset($data["id"])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Doc-type with id " . $this->model->getId() . " doesn't exist");
        }
    }

    /**
     * @throws \Exception
     */
    public function save() {

        $ts = time();
        if(!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        try {
            $dataRaw = get_object_vars($this->model);
            $data = [];
            $allowedProperties = ["id","name","module","controller",
                "action","template","type","priority","creationDate","modificationDate"];

            foreach($dataRaw as $key => $value) {
                if(in_array($key, $allowedProperties)) {
                    $data[$key] = $value;
                }
            }
            $this->db->insertOrUpdate($data, $this->model->getId());
        }
        catch (\Exception $e) {
            throw $e;
        }

        if(!$this->model->getId()) {
            $this->model->setId($this->db->getLastInsertId());
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete($this->model->getId());
    }

}
