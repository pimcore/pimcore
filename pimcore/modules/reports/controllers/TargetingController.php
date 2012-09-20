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

class Reports_TargetingController extends Pimcore_Controller_Action_Admin {

    public function listAction() {

        $targets = array();
        $list = new Tool_Targeting_List();

        if($this->getParam("documentId")) {
            $list->setCondition("documentId = ?", $this->getParam("documentId"));
        } else {
            $list->setCondition("documentId IS NULL OR documentId = ''");
        }

        foreach($list->load() as $target) {
            $targets[] = array(
                "id" => $target->getId(),
                "text" => $target->getName()
            );
        }

        $this->_helper->json($targets);
    }

    public function addAction() {

        $target = new Tool_Targeting();
        $target->setName($this->getParam("name"));

        if($this->getParam("documentId")) {
            $target->setDocumentId($this->getParam("documentId"));
        }

        $target->save();


        $this->_helper->json(array("success" => true, "id" => $target->getId()));
    }

    public function deleteAction() {

        $success = false;

        $target = Tool_Targeting::getById($this->getParam("id"));
        if($target) {
            $target->delete();
            $success = true;
        }

        $this->_helper->json(array("success" => $success));
    }

    public function getAction() {

        $target = Tool_Targeting::getById($this->getParam("id"));
        $redirectUrl = $target->getActions()->getRedirectUrl();
        if(is_numeric($redirectUrl)) {
            $doc = Document::getById($redirectUrl);
            if($doc instanceof Document) {
                $target->getActions()->redirectUrl = $doc->getFullPath();
            }
        }

        $this->_helper->json($target);
    }

    public function saveAction() {

        $data = Zend_Json::decode($this->getParam("data"));

        $target = Tool_Targeting::getById($this->getParam("id"));
        $target->setValues($data["settings"]);

        $target->setConditions($data["conditions"]);

        $actions = new Tool_Targeting_Actions();
        $actions->setRedirectEnabled($data["actions"]["redirect.enabled"]);
        $actions->setRedirectUrl($data["actions"]["redirect.url"]);
        $actions->setRedirectCode($data["actions"]["redirect.code"]);
        $actions->setEventEnabled($data["actions"]["event.enabled"]);
        $actions->setEventKey($data["actions"]["event.key"]);
        $actions->setEventValue($data["actions"]["event.value"]);
        $actions->setProgrammaticallyEnabled($data["actions"]["programmatically.enabled"]);
        $actions->setCodesnippetEnabled($data["actions"]["codesnippet.enabled"]);
        $actions->setCodesnippetCode($data["actions"]["codesnippet.code"]);
        $actions->setCodesnippetSelector($data["actions"]["codesnippet.selector"]);
        $actions->setCodesnippetPosition($data["actions"]["codesnippet.position"]);
        $target->setActions($actions);

        $target->save();

        $this->_helper->json(array("success" => true));
    }

    public function createVariantAction () {

        $targeting = Tool_Targeting::getById($this->getParam("tragetingId"));
        $page = Document::getById($this->getParam("documentId"));
        $docService = new Document_Service($this->getUser());
        $variant = $docService->copyAsChild($page,$page,true);
        $variant->setKey(Element_Service::getSaveCopyName("document", $page->getKey() . "_targeting_" . Pimcore_File::getValidFilename($targeting->getName()), $page));
        $variant->save();

        $this->_helper->json(array("id" => $variant->getId(), "path" => $variant->getFullPath()));
    }
}
