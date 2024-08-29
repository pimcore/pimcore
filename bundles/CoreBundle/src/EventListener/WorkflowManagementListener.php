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
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\WorkflowState;
use Pimcore\Workflow\Manager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

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
