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

namespace Pimcore\Workflow\EventSubscriber;

use Pimcore\Event\Workflow\GlobalActionEvent;
use Pimcore\Event\WorkflowEvents;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Workflow;
use Pimcore\Workflow\Transition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotesSubscriber implements EventSubscriberInterface
{
    const ADDITIONAL_DATA_NOTES_COMMENT = 'notes';
    const ADDITIONAL_DATA_NOTES_ADDITIONAL_FIELDS = 'additional';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var array
     */
    private $additionalData = [];

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Event $event
     *
     * @throws ValidationException
     */
    public function onWorkflowEnter(Event $event)
    {
        if (!$this->checkEvent($event)) {
            return;
        }

        /** @var AbstractElement $subject */
        $subject = $event->getSubject();
        /** @var Transition $transition */
        $transition = $event->getTransition();

        $this->handleNotesPreWorkflow($transition, $subject);
    }

    /**
     * @param Event $event
     *
     * @throws ValidationException
     */
    public function onWorkflowCompleted(Event $event)
    {
        if (!$this->checkEvent($event)) {
            return;
        }

        /** @var AbstractElement $subject */
        $subject = $event->getSubject();
        /** @var Transition $transition */
        $transition = $event->getTransition();

        $this->handleNotesPostWorkflow($transition, $subject);
    }

    /**
     * @param GlobalActionEvent $event
     *
     * @throws ValidationException
     */
    public function onPreGlobalAction(GlobalActionEvent $event)
    {
        if (!$this->checkGlobalActionEvent($event)) {
            return;
        }

        $subject = $event->getSubject();
        $globalAction = $event->getGlobalAction();

        $this->handleNotesPreWorkflow($globalAction, $subject);
    }

    /**
     * @param GlobalActionEvent $event
     *
     * @throws ValidationException
     */
    public function onPostGlobalAction(GlobalActionEvent $event)
    {
        if (!$this->checkGlobalActionEvent($event)) {
            return;
        }

        $subject = $event->getSubject();
        $globalAction = $event->getGlobalAction();

        $this->handleNotesPostWorkflow($globalAction, $subject);
    }

    /**
     * @param Workflow\Notes\NotesAwareInterface $notesAware
     * @param AbstractElement $subject
     *
     * @throws ValidationException
     */
    private function handleNotesPreWorkflow(Workflow\Notes\NotesAwareInterface $notesAware, AbstractElement $subject)
    {
        if (($setterFn = $notesAware->getNotesCommentSetterFn()) && ($notes = $this->getNotesComment())) {
            $subject->$setterFn($notes);
        }

        foreach ($notesAware->getNotesAdditionalFields() as $additionalFieldConfig) {
            $data = $this->getAdditionalDataForField($additionalFieldConfig);

            //check required
            if ($additionalFieldConfig['required'] && (empty($data) || !$data)) {
                $label = isset($additionalFieldConfig['title']) && strlen($additionalFieldConfig['title']) > 0
                    ? $additionalFieldConfig['title']
                    : $additionalFieldConfig['name'];

                throw new ValidationException(
                    $this->translator->trans('workflow_notes_requred_field_message', [$label], 'admin')
                );
            }

            //work out whether or not to set the value directly to the object or to add it to the note data
            if (!empty($additionalFieldConfig['setterFn'])) {
                $setterFn = $additionalFieldConfig['setterFn'];
                $subject->$setterFn($this->getAdditionalDataForField($additionalFieldConfig));
            }
        }
    }

    /**
     * @param Workflow\Notes\NotesAwareInterface $notesAware
     * @param AbstractElement $subject
     *
     * @throws ValidationException
     */
    private function handleNotesPostWorkflow(Workflow\Notes\NotesAwareInterface $notesAware, AbstractElement $subject)
    {
        $additionalFieldsData = [];
        foreach ($notesAware->getNotesAdditionalFields() as $additionalFieldConfig) {
            /**
             * Additional Field example
             * [
            'name' => 'dateLastContacted',
            'fieldType' => 'date',
            'label' => 'Date of Conversation',
            'required' => true,
            'setterFn' => ''
            ]
             */

            //work out whether or not to set the value directly to the object or to add it to the note data
            if (empty($additionalFieldConfig['setterFn'])) {
                $additionalFieldsData[] = Workflow\Service::createNoteData($additionalFieldConfig, $this->getAdditionalDataForField($additionalFieldConfig));
            }
        }

        Workflow\Service::createActionNote(
            $subject,
            $notesAware->getNotesType(),
            $notesAware->getNotesTitle(),
            $this->getNotesComment(),
            $additionalFieldsData
        );
    }

    /**
     * check's if the event subscriber should be executed
     *
     * @param Event $event
     *
     * @return bool
     */
    private function checkEvent(Event $event): bool
    {
        return $this->isEnabled()
               && $event->getTransition() instanceof Transition
               && $event->getSubject() instanceof AbstractElement;
    }

    private function checkGlobalActionEvent(GlobalActionEvent $event): bool
    {
        return $this->isEnabled()
               && $event->getSubject() instanceof AbstractElement;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return array
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    /**
     * @param array $additionalData
     */
    public function setAdditionalData(array $additionalData = []): void
    {
        $this->additionalData = $additionalData;
    }

    private function getAdditionalDataForField(array $fieldConfig)
    {
        $additional = $this->getAdditionalFields();

        $data = $additional[$fieldConfig['name']];

        if ($fieldConfig['fieldType'] === 'checkbox') {
            return $data === 'true';
        }

        return $data;
    }

    private function getNotesComment(): string
    {
        return $this->additionalData[self::ADDITIONAL_DATA_NOTES_COMMENT] ?? '';
    }

    private function getAdditionalFields(): array
    {
        return $this->additionalData[self::ADDITIONAL_DATA_NOTES_ADDITIONAL_FIELDS] ?? [];
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.completed' => ['onWorkflowCompleted', 1],
            'workflow.enter' => 'onWorkflowEnter',
            WorkflowEvents::PRE_GLOBAL_ACTION => 'onPreGlobalAction',
            WorkflowEvents::POST_GLOBAL_ACTION => 'onPostGlobalAction',
        ];
    }
}
