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

namespace Pimcore\Workflow;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\AbstractElement;
use Symfony\Component\Workflow\Workflow;

class ActionsButtonService
{
    /**
     * @var Manager
     */
    private $workflowManager;

    public function __construct(Manager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param Workflow $workflow
     * @param AbstractElement $element
     *
     * @return array
     */
    public function getAllowedTransitions(Workflow $workflow, AbstractElement $element)
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
            ];
        }

        return $allowedTransitions;
    }

    /**
     * @param Workflow $workflow
     * @param AbstractElement $element
     *
     * @return array
     */
    public function getGlobalActions(Workflow $workflow, AbstractElement $element)
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

    /**
     * @param AbstractObject $object
     * @param array $notes
     *
     * @return array
     */
    private function enrichNotes(AbstractObject $object, array $notes)
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
