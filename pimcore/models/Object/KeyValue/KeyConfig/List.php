<?php
class Object_KeyValue_KeyConfig_List extends Pimcore_Model_List_Abstract {

    /**
     * Contains the results of the list. They are all an instance of KeyValue_KeyConfig
     *
     * @var array
     */
    public $list = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    public function getList() {
        return $this->list;
    }

    /**
     * @param array
     * @return void
     */
    public function setList($theList) {
        $this->list = $theList;
    }

}
