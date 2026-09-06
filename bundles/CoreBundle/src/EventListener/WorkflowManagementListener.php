<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Exception;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\WorkflowState;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\MarkingStore\PendingMarkingStoreInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
class WorkflowManagementListener implements EventSubscriberInterface
{
    protected bool $enabled = true;

    public function __construct(
        private Manager $workflowManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_ADD => 'onElementPostAdd',
            DocumentEvents::POST_ADD => 'onElementPostAdd',
            AssetEvents::POST_ADD => 'onElementPostAdd',

            DataObjectEvents::POST_UPDATE => 'onElementPostUpdate',
            DocumentEvents::POST_UPDATE => 'onElementPostUpdate',
            AssetEvents::POST_UPDATE => 'onElementPostUpdate',

            DataObjectEvents::POST_DELETE => 'onElementPostDelete',
            DocumentEvents::POST_DELETE => 'onElementPostDelete',
            AssetEvents::POST_DELETE => 'onElementPostDelete',
        ];
    }

    /**
     * Set initial place if defined on element create.
     */
    public function onElementPostAdd(ElementEventInterface $e): void
    {
        /** @var Asset|Document|ConcreteObject $element */
        $element = $e->getElement();

        $this->persistPendingWorkflowMarkings($element);

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
     * Persist workflow markings that were kept pending on the element (e.g. by a
     * transition with changePublishedState "save_version") once the element is
     * fully saved. Version-only saves keep them pending, as they belong to the draft.
     */
    public function onElementPostUpdate(ElementEventInterface $e): void
    {
        if ($e->hasArgument('saveVersionOnly')) {
            return;
        }

        $this->persistPendingWorkflowMarkings($e->getElement());
    }

    private function persistPendingWorkflowMarkings(ElementInterface $element): void
    {
        if (!$element instanceof AbstractElement) {
            return;
        }

        foreach (array_keys($element->getPendingWorkflowMarkings()) as $workflowName) {
            // Resolve the workflow by name on purpose: the pending place was set while the workflow
            // applied to the element, and re-evaluating the support strategy (e.g. an expression)
            // against the content being published must not silently drop it.
            $workflow = $this->getWorkflowByName($workflowName);
            if (!$workflow) {
                continue;
            }

            $markingStore = $workflow->getMarkingStore();
            if ($markingStore instanceof PendingMarkingStoreInterface) {
                $markingStore->persistPendingMarking($element);
            }
        }
    }

    private function getWorkflowByName(string $workflowName): ?WorkflowInterface
    {
        try {
            return $this->workflowManager->getWorkflowByName($workflowName);
        } catch (LogicException) {
            // the workflow the pending place belongs to is not configured (anymore)
            return null;
        }
    }

    /**
     * Cleanup status information on element delete
     *
     */
    public function onElementPostDelete(ElementEventInterface $e): void
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

    private function enrichNotes(DataObject\AbstractObject $object, array $notes): array
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
     * @throws Exception
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
            throw new Exception('No element found in event');
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
