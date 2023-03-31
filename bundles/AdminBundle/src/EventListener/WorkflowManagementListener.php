<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Bundle\AdminBundle\Event\AdminEvents;
use Pimcore\Bundle\AdminBundle\Service\Workflow\ActionsButtonService;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;
use Pimcore\Model\Element\ElementInterface;
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
            AdminEvents::OBJECT_GET_PRE_SEND_DATA => 'onAdminElementGetPreSendData',
            AdminEvents::ASSET_GET_PRE_SEND_DATA => 'onAdminElementGetPreSendData',
            AdminEvents::DOCUMENT_GET_PRE_SEND_DATA => 'onAdminElementGetPreSendData',
        ];
    }

    /**
     * Fired before information is sent back to the admin UI about an element
     *
     * @param GenericEvent $e
     *
     * @throws \Exception
     */
    public function onAdminElementGetPreSendData(GenericEvent $e): void
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
     * @throws \Exception
     */
    private static function extractElementFromEvent(GenericEvent $e): ElementInterface
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

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
