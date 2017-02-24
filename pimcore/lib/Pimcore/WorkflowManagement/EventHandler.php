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

namespace Pimcore\WorkflowManagement;

use Pimcore\Model\Element\AbstractElement;
use Pimcore\WorkflowManagement\Workflow;
use Pimcore\Tool\Admin;
use Pimcore\Model\Object;
use Pimcore\Model\Object\Concrete as ConcreteObject;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object\ClassDefinition;

class EventHandler
{

    /**
     * Ensures that any elements which support workflows are given the correct default state / status
     * @param \Zend_EventManager_Event $e
     */
    public static function elementPostAdd(\Zend_EventManager_Event $e)
    {
        /**
         * @var Asset|Document|ConcreteObject $element
         */
        $element = $e->getTarget();

        if (!self::isDisabled() && Workflow\Manager::elementHasWorkflow($element)) {
            $manager = Workflow\Manager\Factory::getManager($element);
            $manager->setElementState($manager->getWorkflow()->getDefaultState());
            $manager->setElementStatus($manager->getWorkflow()->getDefaultStatus());
        }
    }

    /**
     * Cleanup status information on element delete
     *
     * @param \Zend_EventManager_Event $e
     */
    public static function elementPostDelete(\Zend_EventManager_Event $e)
    {
        /**
         * @var Asset|Document|ConcreteObject $element
         */
        $element = $e->getTarget();

        if (Workflow\Manager::elementHasWorkflow($element)) {
            $manager = Workflow\Manager\Factory::getManager($element);
            $workflowState = $manager->getWorkflowStateForElement();
            if ($workflowState) {
                $workflowState->delete();
            }
        }
    }


    /**
     * Fired before information is sent back to the admin UI about an element
     * @param \Zend_EventManager_Event $e
     * @throws \Exception
     */
    public static function adminElementGetPreSendData($e)
    {
        $element = self::extractElementFromEvent($e);
        $returnValueContainer = $e->getParam('returnValueContainer');
        $data = $returnValueContainer->getData();

        //create a new namespace for WorkflowManagement
        //set some defaults
        $data['workflowManagement'] = [
            'hasWorkflowManagement' => false,
        ];

        if (Workflow\Manager::elementCanAction($element)) {
            $data['workflowManagement']['hasWorkflowManagement'] = true;

            //see if we can change the layout
            $currentUser = Admin::getCurrentUser();
            $manager = Workflow\Manager\Factory::getManager($element, $currentUser);

            $data['workflowManagement']['workflowName'] = $manager->getWorkflow()->getName();

            //get the state and status
            $state = $manager->getElementState();
            $data['workflowManagement']['state'] = $manager->getWorkflow()->getStateConfig($state);
            $status = $manager->getElementStatus();
            $data['workflowManagement']['status'] = $manager->getWorkflow()->getStatusConfig($status);


            if ($element instanceof ConcreteObject) {
                $workflowLayoutId = $manager->getObjectLayout();

                //check for !is_null here as we might want to specify 0 in the workflow config
                if (!is_null($workflowLayoutId)) {
                    //load the new layout into the object container

                    $validLayouts = Object\Service::getValidLayouts($element);

                    //check that the layout id is valid before trying to load
                    if (!empty($validLayouts)) {

                        //todo check user permissions again
                        if ($validLayouts && $validLayouts[$workflowLayoutId]) {
                            $customLayout = ClassDefinition\CustomLayout::getById($workflowLayoutId);
                            $customLayoutDefinition = $customLayout->getLayoutDefinitions();
                            Object\Service::enrichLayoutDefinition($customLayoutDefinition, $e->getParam('object'));
                            $data["layout"] = $customLayoutDefinition;
                        }
                    }
                }
            }
        }

        $returnValueContainer->setData($data);
    }


    /**
     * @param \Zend_EventManager_Event $e
     * @return AbstractElement
     * @throws \Exception
     */
    private static function extractElementFromEvent(\Zend_EventManager_Event $e)
    {
        $element = $e->getParam("object");
        if (empty($element)) {
            $element = $e->getParam("asset");
        }
        if (empty($element)) {
            $element = $e->getParam("document");
        }
        if (empty($element)) {
            throw new \Exception("No element found in event");
        }

        return $element;
    }

    /**
     * Disables events for the current request
     */
    public static function disable()
    {
        \Pimcore\Cache\Runtime::set('workflow_events_disable_cur_process', true);
    }

    public static function enable()
    {
        \Pimcore\Cache\Runtime::set('workflow_events_disable_cur_process', false);
    }

    /**
     * @return bool
     */
    public static function isDisabled()
    {
        return (\Pimcore\Cache\Runtime::isRegistered('workflow_events_disable_cur_process') && \Pimcore\Cache\Runtime::get('workflow_events_disable_cur_process'));
    }
}
