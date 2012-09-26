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

class Admin_HardlinkController extends Pimcore_Controller_Action_Admin_Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->getParam("id"), "document")
            ));
        }
        Element_Editlock::lock($this->getParam("id"), "document");

        $link = Document_Hardlink::getById($this->getParam("id"));
        $link->idPath = Pimcore_Tool::getIdPathForElement($link);
        $link->userPermissions = $link->getUserPermissions();
        $link->setLocked($link->isLocked());

        if($link->getSourceDocument()) {
            $link->sourcePath = $link->getSourceDocument()->getFullpath();
        }

        $this->minimizeProperties($link);

        if ($link->isAllowed("view")) {
            $this->_helper->json($link);
        }

        $this->_helper->json(false);
    }

    public function saveAction() {
        if ($this->getParam("id")) {
            $link = Document_Hardlink::getById($this->getParam("id"));
            $this->setValuesToDocument($link);

            $link->setModificationDate(time());
            $link->setUserModification($this->getUser()->getId());

            if ($this->getParam("task") == "unpublish") {
                $link->setPublished(false);
            }
            if ($this->getParam("task") == "publish") {
                $link->setPublished(true);
            }

            // only save when publish or unpublish
            if (($this->getParam("task") == "publish" && $link->isAllowed("publish")) || ($this->getParam("task") == "unpublish" && $link->isAllowed("unpublish"))) {
                $link->save();

                $this->_helper->json(array("success" => true));
            }
        }

        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document_Hardlink $link) {

        // data
        $data = Zend_Json::decode($this->getParam("data"));

        $sourceId = null;
        if($sourceDocument = Document::getByPath($data["sourcePath"])) {
            $sourceId = $sourceDocument->getId();
        }
        $link->setSourceId($sourceId);

        $link->setValues($data);
        $this->addPropertiesToDocument($link);
    }

}
