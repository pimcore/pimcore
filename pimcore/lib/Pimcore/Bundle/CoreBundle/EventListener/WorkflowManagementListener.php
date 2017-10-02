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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Event\AdminEvents;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Tool\Admin;
use Pimcore\WorkflowManagement\Workflow;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class WorkflowManagementListener implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectEvents::POST_ADD  => 'onElementPostAdd',
            DocumentEvents::POST_ADD  => 'onElementPostAdd',
            AssetEvents::POST_ADD  => 'onElementPostAdd',

            DataObjectEvents::POST_DELETE => 'onElementPostDelete',
            DocumentEvents::POST_DELETE => 'onElementPostDelete',
            AssetEvents::POST_DELETE => 'onElementPostDelete',

            AdminEvents::OBJECT_GET_PRE_SEND_DATA => 'onAdminElementGetPreSendData',
            AdminEvents::ASSET_GET_PRE_SEND_DATA => 'onAdminElementGetPreSendData',
            AdminEvents::DOCUMENT_GET_PRE_SEND_DATA => 'onAdminElementGetPreSendData',
        ];
    }

    /**
     * Ensures that any elements which support workflows are given the correct default state / status
     *
     * @param ElementEventInterface $e
     */
    public function onElementPostAdd(ElementEventInterface $e)
    {
        /**
         * @var Asset|Document|ConcreteObject $element
         */
        $element = $e->getElement();

        if ($this->isEnabled() && Workflow\Manager::elementHasWorkflow($element)) {
            $manager = Workflow\Manager\Factory::getManager($element);
            $manager->setElementState($manager->getWorkflow()->getDefaultState());
            $manager->setElementStatus($manager->getWorkflow()->getDefaultStatus());
        }
    }

    /**
     * Cleanup status information on element delete
     *
     * @param ElementEventInterface $e
     */
    public function onElementPostDelete(ElementEventInterface $e)
    {
        /**
         * @var Asset|Document|ConcreteObject $element
         */
        $element = $e->getElement();

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
     *
     * @param GenericEvent $e
     *
     * @throws \Exception
     */
    public function onAdminElementGetPreSendData(GenericEvent $e)
    {
        $element = self::extractElementFromEvent($e);
        $data = $e->getArgument('data');

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

                    $validLayouts = DataObject\Service::getValidLayouts($element);

                    //check that the layout id is valid before trying to load
                    if (!empty($validLayouts)) {

                        //todo check user permissions again
                        if ($validLayouts && $validLayouts[$workflowLayoutId]) {
                            $customLayout = ClassDefinition\CustomLayout::getById($workflowLayoutId);
                            $customLayoutDefinition = $customLayout->getLayoutDefinitions();
                            DataObject\Service::enrichLayoutDefinition($customLayoutDefinition, $e->getParam('object'));
                            $data['layout'] = $customLayoutDefinition;
                        }
                    }
                }
            }
        }

        $e->setArgument('data', $data);
    }

    /**
     * @param GenericEvent $e
     *
     * @return AbstractElement
     *
     * @throws \Exception
     */
    private static function extractElementFromEvent(GenericEvent $e)
    {
        $element = null;

        foreach (['object', 'asset', 'document'] as $type) {
            if ($e->hasArgument($type)) {
                $element = $e->getArgument($type);
            }
        }

        if (empty($element)) {
            throw new \Exception('No element found in event');
        }

        return $element;
    }

    public function enable()
    {
        $this->enabled = true;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}
