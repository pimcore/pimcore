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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
class Search_Backend_Data_List_Resource extends Pimcore_Model_List_Resource_Abstract  {


     /**
     * Loads a list of entries for the specicifies parameters, returns an array of Search_Backend_Data
     *
     * @return array
     */
    public function load() {

        $entries = array();
        $data = $this->db->fetchAll("SELECT * FROM search_backend_data" .  $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
     
        foreach ($data as $entryData) {

             if($entryData['maintype']=='document'){
                $element = Document::getById($entryData['id']);
             } else  if($entryData['maintype']=='asset'){
                $element = Asset::getById($entryData['id']);
             } else  if($entryData['maintype']=='object'){
                $element = Object_Abstract::getById($entryData['id']);
             } else {
                Logger::err("unknown maintype ");
             }
            if($element){
                $entry = new Search_Backend_Data();
                $entry->setId(new Search_Backend_Data_Id($element));
                $entry->setFullPath($entryData['fullpath']);
                $entry->setType($entryData['type']);
                $entry->setSubtype($entryData['subtype']);
                $entry->setUserOwner($entryData['userowner']);
                $entry->setUserModification($entryData['usermodification']);
                $entry->setCreationDate($entryData['creationdate']);
                $entry->setModificationDate($entryData['modificationdate']);
                $entry->setPublished($entryData['published']=== 0 ? false : true);
                $entries[]=$entry;
            }
        }
        $this->model->setEntries($entries);
        return $entries;
    }

    public function getTotalCount() {
        $amount = $this->db->fetchOne("SELECT COUNT(*) as amount FROM search_backend_data" . $this->getCondition() . $this->getGroupBy(), $this->model->getConditionVariables());
        return $amount;
    }

    public function getCount() {
        if (count($this->model->getEntries()) > 0) {
            return count($this->model->getEntries());
        }

        $amount = $this->db->fetchOne("SELECT COUNT(*) as amount FROM search_backend_data "  . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $amount;
    }

    protected function getCondition() {
        if ($cond = $this->model->getCondition()) {
            return " WHERE " . $cond . " ";
        }
        return "";
    }

}