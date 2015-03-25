<?php

/**
 * Abstract base class for filter definition type field collections
 */
abstract class OnlineShop_Framework_AbstractFilterDefinitionType extends \Pimcore\Model\Object\Fieldcollection\Data\AbstractData {

    protected $metaData = [];

    /**
     * @return array
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * @param array $metaData
     *
     * @return $this
     */
    public function setMetaData($metaData)
    {
        $this->metaData = $metaData;
        return $this;
    }


    /**
    * @return string
    */
    public abstract function getLabel();

    /**
    * @return string
    */
    public abstract function getField();

    /**
     * @return string
     */
    public abstract function getScriptPath();

    /**
     * @return string
     */
    public function getRequiredFilterField() {
        return "";
    }

}
