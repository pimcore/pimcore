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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Search\Backend\Data;

use Pimcore\Model;

class Resource extends \Pimcore\Model\Resource\AbstractResource {

    /**
     * @param $element
     * @throws
     */
    public function getForElement($element) {
        try {

            if($element instanceof Model\Document){
                $maintype = "document";
            } else if ($element instanceof Model\Asset){
                $maintype = "asset";
            } else if ($element instanceof Model\Object\AbstractObject){
                $maintype = "object";
            } else {
                throw Exception("unknown type of element with id [ ".$element->getId()." ] ");
            }

			$data = $this->db->fetchRow("SELECT * FROM search_backend_data WHERE id= ? AND maintype = ? ", array($element->getId(),$maintype));
            if(is_array($data)){
                $this->assignVariablesToModel($data);
                $this->model->setId(new Model\Search\Backend\Data\Id($element));
            }

		}
		catch (\Exception $e){}
    }


    /**
     *
     */
    public function save() {
        try {
            $data = array(
                "id" => $this->model->getId()->getId(),
                "fullpath" => $this->model->getFullPath(),
                "maintype" => $this->model->getId()->getType(),
                "type" => $this->model->getType(),
                "subtype" => $this->model->getSubtype(),
                "published" => $this->model->isPublished(),
                "creationdate" => $this->model->getCreationDate(),
                "modificationdate" => $this->model->getmodificationDate(),
                "userowner" => $this->model->getUserOwner(),
                "usermodification" => $this->model->getUserModification(),
                "data" => $this->model->getData(),
                "properties" => $this->model->getProperties()
            );

            $this->db->insertOrUpdate("search_backend_data", $data);

        } catch (\Exception $e) {
            \Logger::error($e);
        }

    }

    /**
     * Deletes from database
     *
     * @return void
     */
    public function delete() {
        if($this->model->getId() instanceof Model\Search\Backend\Data\Id ){
            $this->db->delete("search_backend_data", "id='" . $this->model->getId()->getId() . "' AND maintype ='" .$this->model->getId()->getType() . "'");
        } else {
           \Logger::alert("Cannot delete Search\\Backend\\Data, ID is empty");
        }
    }
}