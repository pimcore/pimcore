<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 27.05.13
 * Time: 22:26
 */

class Deployment_Target_Execution_Resource extends Pimcore_Model_Resource_Abstract {

    const TABLE_NAME = 'deployment_target';
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

    protected function create(){
        $this->db->insert(self::TABLE_NAME,array());
        $this->model->setId($this->db->lastInsertId(self::TABLE_NAME));
        $this->model->setCreationDate(time());
    }
}