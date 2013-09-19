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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Reports_TargetingController extends Pimcore_Controller_Action_Admin {

    public function init() {
        parent::init();

        // check permissions
        $notRestrictedActions = array("persona-list");
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("targeting");
        }
    }

    /* RULES */

    public function ruleListAction() {

        $targets = array();
        $list = new Tool_Targeting_Rule_List();

        foreach($list->load() as $target) {
            $targets[] = array(
                "id" => $target->getId(),
                "text" => $target->getName(),
                "qtip" => $target->getId()
            );
        }

        $this->_helper->json($targets);
    }

    public function ruleAddAction() {

        $target = new Tool_Targeting_Rule();
        $target->setName($this->getParam("name"));
        $target->save();

        $this->_helper->json(array("success" => true, "id" => $target->getId()));
    }

    public function ruleDeleteAction() {

        $success = false;

        $target = Tool_Targeting_Rule::getById($this->getParam("id"));
        if($target) {
            $target->delete();
            $success = true;
        }

        $this->_helper->json(array("success" => $success));
    }

    public function ruleGetAction() {

        $target = Tool_Targeting_Rule::getById($this->getParam("id"));
        $redirectUrl = $target->getActions()->getRedirectUrl();
        if(is_numeric($redirectUrl)) {
            $doc = Document::getById($redirectUrl);
            if($doc instanceof Document) {
                $target->getActions()->redirectUrl = $doc->getFullPath();
            }
        }

        $this->_helper->json($target);
    }

    public function ruleSaveAction() {

        $data = Zend_Json::decode($this->getParam("data"));

        $target = Tool_Targeting_Rule::getById($this->getParam("id"));
        $target->setValues($data["settings"]);

        $target->setConditions($data["conditions"]);

        $actions = new Tool_Targeting_Rule_Actions();
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
        $actions->setPersonaId($data["actions"]["persona.id"]);
        $actions->setPersonaEnabled($data["actions"]["persona.enabled"]);
        $target->setActions($actions);

        $target->save();

        $this->_helper->json(array("success" => true));
    }





    /* PERSONAS */

    public function personaListAction() {

        $personas = array();
        $list = new Tool_Targeting_Persona_List();

        foreach($list->load() as $persona) {
            $personas[] = array(
                "id" => $persona->getId(),
                "text" => $persona->getName(),
                "qtip" => $persona->getId()
            );
        }

        $this->_helper->json($personas);
    }

    public function personaAddAction() {

        $persona = new Tool_Targeting_Persona();
        $persona->setName($this->getParam("name"));
        $persona->save();

        $this->_helper->json(array("success" => true, "id" => $persona->getId()));
    }

    public function personaDeleteAction() {

        $success = false;

        $persona = Tool_Targeting_Persona::getById($this->getParam("id"));
        if($persona) {
            $persona->delete();
            $success = true;
        }

        $this->_helper->json(array("success" => $success));
    }

    public function personaGetAction() {

        $persona = Tool_Targeting_Persona::getById($this->getParam("id"));
        $this->_helper->json($persona);
    }

    public function personaSaveAction() {

        $data = Zend_Json::decode($this->getParam("data"));

        $persona = Tool_Targeting_Persona::getById($this->getParam("id"));
        $persona->setValues($data["settings"]);

        $persona->setConditions($data["conditions"]);
        $persona->save();

        $this->_helper->json(array("success" => true));
    }
}
