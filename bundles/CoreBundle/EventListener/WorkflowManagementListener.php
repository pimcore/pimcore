<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\WorkflowState;
use Pimcore\Workflow\ActionsButtonService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Place;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
class WorkflowManagementListener implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    protected bool $enabled = true;

    public function __construct(
        private Manager $workflowManager,
        private Place\StatusInfo $placeStatusInfo,
        private RequestStack $requestStack,
        private ActionsButtonService $actionsButtonService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_ADD => 'onElementPostAdd',
            DocumentEvents::POST_ADD => 'onElementPostAdd',
            AssetEvents::POST_ADD => 'onElementPostAdd',

            DataObjectEvents::POST_DELETE => 'onElementPostDelete',
            DocumentEvents::POST_DELETE => 'onElementPostDelete',
            AssetEvents::POST_DELETE => 'onElementPostDelete',

            AdminEvents::OBJECT_GET_PRE_SEND_DATA => 'onAdminElementGetPreSendData',
            AdminEvents::ASSET_GET_PRE_SEND_DATA => 'onAdminElementGetPreSendData',
            AdminEvents::DOCUMENT_GET_PRE_SEND_DATA => 'onAdminElementGetPreSendData',
        ];
    }

    /**
     * Set initial place if defined on element create.
     */
    public function onElementPostAdd(ElementEventInterface $e): void
    {
        /** @var Asset|Document|ConcreteObject $element */
        $element = $e->getElement();

        foreach ($this->workflowManager->getAllWorkflows() as $workflowName) {
            $workflow = $this->workflowManager->getWorkflowIfExists($element, $workflowName);
            if (!$workflow) {
                continue;
            }

            $hasInitialPlaceConfig = count($this->workflowManager->getInitialPlacesForWorkflow($workflow)) > 0;

            // calling getMarking will ensure the initial place is set
            if ($hasInitialPlaceConfig) {
                $workflow->getMarking($element);
            }
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

        $list = new WorkflowState\Listing;
        $list->setCondition('cid = ? and ctype = ?', [$element->getId(), Service::getElementType($element)]);

        foreach ($list->load() as $item) {
            $item->delete();
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

        foreach ($this->workflowManager->getAllWorkflows() as $workflowName) {
            $workflow = $this->workflowManager->getWorkflowIfExists($element, $workflowName);
            $workflowConfig = $this->workflowManager->getWorkflowConfig($workflowName);

            if (empty($workflow)) {
                continue;
            }

            $data['workflowManagement']['hasWorkflowManagement'] = true;
            $data['workflowManagement']['workflows'] = $data['workflowManagement']['workflows'] ?? [];

            // Fix: places stored as empty string ("") considered uninitialized prior to Symfony 4.4.8
            $this->workflowManager->ensureInitialPlace($workflowName, $element);

            $allowedTransitions = $this->actionsButtonService->getAllowedTransitions($workflow, $element);
            $globalActions = $this->actionsButtonService->getGlobalActions($workflow, $element);

            $data['workflowManagement']['workflows'][] = [
                'name' => $workflow->getName(),
                'label' => $workflowConfig->getLabel(),
                'allowedTransitions' => $allowedTransitions,
                'globalActions' => $globalActions,
            ];

            $marking = $workflow->getMarking($element);

            if (!count($marking->getPlaces())) {
                continue;
            }

            $permissionsRespected = false;
            foreach ($this->workflowManager->getOrderedPlaceConfigs($workflow, $marking) as $placeConfig) {
                if (!$permissionsRespected && !empty($placeConfig->getPermissions($workflow, $element))) {
                    $data['userPermissions'] = array_merge(
                        (array)$data['userPermissions'],
                        $placeConfig->getUserPermissions($workflow, $element)
                    );

                    if ($element instanceof ConcreteObject) {
                        $workflowLayoutId = $placeConfig->getObjectLayout($workflow, $element);
                        $hasSelectedCustomLayout = $this->requestStack->getMainRequest(
                            ) && $this->requestStack->getMainRequest()->query->has(
                                'layoutId'
                            ) && $this->requestStack->getMainRequest()->query->get('layoutId') !== '';

                        if (!is_null($workflowLayoutId) && !$hasSelectedCustomLayout) {
                            //load the new layout into the object container
                            $validLayouts = DataObject\Service::getValidLayouts($element);

                            // check user permissions again
                            if (isset($validLayouts[$workflowLayoutId])) {
                                $customLayout = ClassDefinition\CustomLayout::getById($workflowLayoutId);
                                $customLayoutDefinition = $customLayout->getLayoutDefinitions();
                                DataObject\Service::enrichLayoutDefinition(
                                    $customLayoutDefinition,
                                    $e->getArgument('object')
                                );
                                $data['layout'] = $customLayoutDefinition;
                                $data['currentLayoutId'] = $workflowLayoutId;
                            }
                        }
                    }
                    $permissionsRespected = true;
                }
            }
        }

        if ($data['workflowManagement']['hasWorkflowManagement']) {
            $data['workflowManagement']['statusInfo'] = $this->placeStatusInfo->getToolbarHtml($element);
        }

        $e->setArgument('data', $data);
    }

    /**
     * @param DataObject\AbstractObject $object
     * @param array $notes
     *
     * @return array
     */
    private function enrichNotes(DataObject\AbstractObject $object, array $notes)
    {
        if (!empty($notes['commentGetterFn'])) {
            $commentGetterFn = $notes['commentGetterFn'];
            $notes['commentPrefill'] = $object->$commentGetterFn();
        } elseif (!empty($notes)) {
            $notes['commentPrefill'] = '';
        }

        return $notes;
    }

    /**
     * @param GenericEvent $e
     *
     * @return ElementInterface
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
