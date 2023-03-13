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

namespace Pimcore\Bundle\AdminBundle\Service\Workflow;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

class ActionsButtonService
{
    private Manager $workflowManager;

    public function __construct(Manager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    public function getAllowedTransitions(Workflow $workflow, ElementInterface $element): array
    {
        $allowedTransitions = [];

        /**
         * @var Transition $transition
         */
        foreach ($workflow->getEnabledTransitions($element) as $transition) {
            if (($notes = $transition->getNotes()) && $element instanceof AbstractObject) {
                $notes = $this->enrichNotes($element, $notes);
            }

            $allowedTransitions[] = [
                'name' => $transition->getName(),
                'label' => $transition->getLabel(),
                'iconCls' => $transition->getIconClass(),
                'objectLayout' => $transition->getObjectLayout(),
                'notes' => $notes,
                'unsavedChangesBehaviour' => $transition->getOptions()['unsavedChangesBehaviour'],
            ];
        }

        return $allowedTransitions;
    }

    public function getGlobalActions(Workflow $workflow, ElementInterface $element): array
    {
        $globalActions = [];
        foreach ($this->workflowManager->getGlobalActions($workflow->getName()) as $globalAction) {
            if ($globalAction->isGuardValid($workflow, $element)) {
                if (($notes = $globalAction->getNotes()) && $element instanceof AbstractObject) {
                    $notes = $this->enrichNotes($element, $notes);
                }

                $globalActions[] = [
                    'name' => $globalAction->getName(),
                    'label' => $globalAction->getLabel(),
                    'iconCls' => $globalAction->getIconClass(),
                    'objectLayout' => $globalAction->getObjectLayout(),
                    'notes' => $notes,
                ];
            }
        }

        return $globalActions;
    }

    private function enrichNotes(AbstractObject $object, array $notes): array
    {
        if (!empty($notes['commentGetterFn'])) {
            $commentGetterFn = $notes['commentGetterFn'];
            $notes['commentPrefill'] = $object->$commentGetterFn();
        } elseif (!empty($notes)) {
            $notes['commentPrefill'] = '';
        }

        return $notes;
    }
}
