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

class Admin_FolderController extends Pimcore_Controller_Action_Admin_Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->_getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->_getParam("id"), "document")
            ));
        }
        Element_Editlock::lock($this->_getParam("id"), "document");

        $folder = Document_Folder::getById($this->_getParam("id"));
        //$folder->getPermissions();
        $folder->getPermissionsForUser($this->getUser());
        $folder->idPath = Pimcore_Tool::getIdPathForElement($folder);
        
        $this->minimizeProperties($folder);

        if ($folder->isAllowed("view")) {
            $this->_helper->json($folder);
        }

        $this->_helper->json(false);
    }

    public function saveAction() {
        if ($this->_getParam("id")) {
            $folder = Document_Folder::getById($this->_getParam("id"));
            $folder->getPermissionsForUser($this->getUser());

            $folder->setModificationDate(time());
            $folder->setUserModification($this->getUser()->getId());

            if ($folder->isAllowed("publish")) {
                $this->setValuesToDocument($folder);
                $folder->save();

                $this->_helper->json(array("success" => true));
            }
        }
        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document_Folder $folder) {

        $this->addPropertiesToDocument($folder);

    }

}
