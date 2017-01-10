<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Model\Workflow;

class Admin_WorkflowSettingsController extends \Pimcore\Controller\Action\Admin\Element
{
    public function preDispatch()
    {
        parent::preDispatch();

        $this->checkPermission("workflows");
    }

    public function treeAction()
    {
        $workflows = [];

        $list = new Workflow\Listing();
        $list->load();

        $items = $list->getWorkflows();

        foreach ($items as $item) {
            $workflows[] = [
                "id" => $item->getId(),
                "text" => $item->getName(),
                "leaf" => true,
                "iconCls" => "pimcore_icon_workflow"
            ];
        }

        $this->_helper->json($workflows);
    }

    public function getAction() {
        $id = $this->getParam("id");
        $workflow = Workflow::getById($id);

        if($workflow instanceof Workflow) {
            $this->_helper->json(['success' => true, 'workflow' => get_object_vars($workflow)]);
        }

        $this->_helper->json(['success' => false]);
    }

    public function addAction() {
        $workflow = new Workflow();
        $workflow->setName($this->getParam("name"));
        $workflow->save();

        $this->_helper->json(['success' => true, "id" => $workflow->getId()]);
    }

    public function updateAction() {
        $id = $this->getParam("id");
        $data = $this->getParam("data");
        $workflow = Workflow::getById($id);

        if(!$workflow instanceof Workflow) {
            $this->_helper->json(['success' => false]);
        }

        $data = \Zend_Json::decode($data);

        $classes = $data['settings']['classes'];
        $types = $data['settings']['types'];
        $assetTypes = $data['settings']['assetTypes'];
        $documentTypes = $data['settings']['documentTypes'];

        $workflowSubject = [
            "types" => $types,
            "classes" => $classes,
            "assetTypes" => $assetTypes,
            "documentTypes" => $documentTypes
        ];

        $workflow->setValues($data['settings']);
        $workflow->setWorkflowSubject($workflowSubject);
        $workflow->setStates($data['states']);
        $workflow->setStatuses($data['statuses']);
        $workflow->setActions($data['actions']);
        $workflow->setTransitionDefinitions($data['transitionDefinitions']);
        $workflow->save();

        $this->_helper->json(['success' => true, 'workflow' => get_object_vars($workflow)]);
    }

    public function deleteAction()
    {
        $id = $this->getParam("id");
        $workflow = Workflow::getById($id);

        if($workflow instanceof Workflow) {
            $workflow->delete();
        }

        $this->_helper->json(['success' => true]);
    }

    public function testAction() {
        include PIMCORE_DOCUMENT_ROOT . "/update/4016/postupdate.php";

        exit;
    }
}
