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

class Admin_LinkController extends Pimcore_Controller_Action_Admin_Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->_getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->_getParam("id"), "document")
            ));
        }
        Element_Editlock::lock($this->_getParam("id"), "document");

        $link = Document_Link::getById($this->_getParam("id"));
        $link->getPermissionsForUser($this->getUser());
        $link->setObject(null);
        $link->idPath = Pimcore_Tool::getIdPathForElement($link);
        $this->minimizeProperties($link);

        if ($link->isAllowed("view")) {
            $this->_helper->json($link);
        }

        $this->_helper->json(false);
    }

    public function saveAction() {
        if ($this->_getParam("id")) {
            $link = Document_Link::getById($this->_getParam("id"));
            $link->getPermissionsForUser($this->getUser());

            $this->setValuesToDocument($link);

            $link->setModificationDate(time());
            $link->setUserModification($this->getUser()->getId());

            if ($this->_getParam("task") == "unpublish") {
                $link->setPublished(false);
            }
            if ($this->_getParam("task") == "publish") {
                $link->setPublished(true);
            }

            // only save when publish or unpublish
            if (($this->_getParam("task") == "publish" && $link->isAllowed("publish")) || ($this->_getParam("task") == "unpublish" && $link->isAllowed("unpublish"))) {
                $link->save();

                $this->_helper->json(array("success" => true));
            }
        }

        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document_Link $link) {

        // data
        $data = Zend_Json::decode($this->_getParam("data"));

        if (!empty($data["path"])) {
            if ($document = Document::getByPath($data["path"])) {
                $data["linktype"] = "internal";
                $data["internalType"] = "document";
                $data["internal"] = $document->getId();
            }
            else if ($asset = Asset::getByPath($data["path"])) {
                $data["linktype"] = "internal";
                $data["internalType"] = "asset";
                $data["internal"] = $asset->getId();
            }
            else {
                $data["linktype"] = "direct";
                $data["direct"] = $data["path"];
            }
        }

        unset($data["path"]);

        $link->setValues($data);
        $this->addPropertiesToDocument($link);
    }

}
