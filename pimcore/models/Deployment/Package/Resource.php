<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 27.05.13
 * Time: 22:26
 */

class Deployment_Package_Resource extends Pimcore_Model_Resource_Abstract {

    const TABLE_NAME = 'deployment_packages';
    /**
     * Contains the valid database columns
     *
     * @var array
     */
    protected $validColumnsPage = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns(self::TABLE_NAME);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        $modelVars = get_object_vars($this->model);

        if(!$this->model->getId()){
            $this->create();
        }

        foreach ($modelVars as $key => $value) {
            if (in_array($key, $this->validColumns)) {

                // check if the getter exists
                $getter = "get" . ucfirst($key);
                if(!method_exists($this->model,$getter)) {
                    continue;
                }

                // get the value from the getter
                $value = $this->model->$getter();

                if (is_bool($value)) {
                    $value = (int) $value;
                }
                $data[$key] = $value;
            }
        }
        $this->db->update(self::TABLE_NAME, $data, 'id = ' . $data['id']);
        return $this->model;
    }

    public function getNewVersionByType($type){
        $version = $this->db->fetchOne("SELECT MAX(version)+1 as newVersion FROM " . self::TABLE_NAME . " where `type` = '" . $type ."'");
        if($version == 0){
            $version = 1;
        }
        return $version;
    }

    protected function create(){
        $this->db->insert(self::TABLE_NAME,array());
        $this->model->setId($this->db->lastInsertId(self::TABLE_NAME));
        $this->model->setCreationDate(time());
        if(!$this->model->getVersion()){
            $this->model->setVersion($this->getNewVersionByType($this->model->getType()));
        }
    }

    public function delete(){
        if($this->model->getId()){
            try {
                recursiveDelete($this->model->getPackageDirectory());
                $this->db->delete(self::TABLE_NAME , $this->db->quoteInto("id = ?", $this->model->getId() ));
            }
            catch (Exception $e) {
                throw $e;
            }
        }
    }


    public function getById($id) {
        try {
            $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME ." WHERE id = ?", $id);
        } catch (Exception $e) {}

        if ($data["id"] > 0) {
            $this->assignVariablesToModel($data);
        }
        else {
            throw new Exception("Package with the ID " . $id . " doesn't exists");
        }
    }
}