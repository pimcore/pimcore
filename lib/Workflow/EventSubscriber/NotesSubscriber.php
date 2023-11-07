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

namespace Pimcore\Workflow\EventSubscriber;

use Pimcore\Event\Workflow\GlobalActionEvent;
use Pimcore\Event\WorkflowEvents;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Workflow;
use Pimcore\Workflow\Transition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class NotesSubscriber implements EventSubscriberInterface
{
    const ADDITIONAL_DATA_NOTES_COMMENT = 'notes';

    const ADDITIONAL_DATA_NOTES_ADDITIONAL_FIELDS = 'additional';

    private TranslatorInterface $translator;

    private bool $enabled = true;

    private array $additionalData = [];

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     *
     * @throws ValidationException
     */
    public function onWorkflowEnter(Event $event): void
    {
        if (!$this->checkEvent($event)) {
            return;
        }

        /** @var ElementInterface $subject */
        $subject = $event->getSubject();
        /** @var Transition $transition */
        $transition = $event->getTransition();

        $this->handleNotesPreWorkflow($transition, $subject);
    }

    public function onWorkflowCompleted(Event $event): void
    {
        if (!$this->checkEvent($event)) {
            return;
        }

        /** @var ElementInterface $subject */
        $subject = $event->getSubject();
        /** @var Transition $transition */
        $transition = $event->getTransition();

        $this->handleNotesPostWorkflow($transition, $subject);
    }

    /**
     *
     * @throws ValidationException
     */
    public function onPreGlobalAction(GlobalActionEvent $event): void
    {
        if (!$this->checkGlobalActionEvent($event)) {
            return;
        }

        $subject = $event->getSubject();
        $globalAction = $event->getGlobalAction();

        $this->handleNotesPreWorkflow($globalAction, $subject);
    }

    public function onPostGlobalAction(GlobalActionEvent $event): void
    {
        if (!$this->checkGlobalActionEvent($event)) {
            return;
        }

        $subject = $event->getSubject();
        $globalAction = $event->getGlobalAction();

        $this->handleNotesPostWorkflow($globalAction, $subject);
    }

    /**
     * @throws ValidationException
     */
    private function handleNotesPreWorkflow(Workflow\Notes\NotesAwareInterface $notesAware, ElementInterface $subject): void
    {
        if (($setterFn = $notesAware->getNotesCommentSetterFn()) && ($notes = $this->getNotesComment())) {
            $subject->$setterFn($notes);
        }

        foreach ($notesAware->getNotesAdditionalFields() as $additionalFieldConfig) {
            $data = $this->getAdditionalDataForField($additionalFieldConfig);

            //check required
            if ($additionalFieldConfig['required'] && empty($data)) {
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

    private function handleNotesPostWorkflow(Workflow\Notes\NotesAwareInterface $notesAware, ElementInterface $subject): void
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
     */
    private function checkEvent(Event $event): bool
    {
        return $this->isEnabled()
               && $event->getTransition() instanceof Transition
               && $event->getSubject() instanceof ElementInterface;
    }

    private function checkGlobalActionEvent(GlobalActionEvent $event): bool
    {
        return $this->isEnabled()
               && $event->getSubject() instanceof ElementInterface;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function setAdditionalData(array $additionalData = []): void
    {
        $this->additionalData = $additionalData;
    }

    /**
     * @param array<string, mixed> $fieldConfig
     */
    private function getAdditionalDataForField(array $fieldConfig): mixed
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

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.completed' => ['onWorkflowCompleted', 1],
            'workflow.enter' => 'onWorkflowEnter',
            WorkflowEvents::PRE_GLOBAL_ACTION => 'onPreGlobalAction',
            WorkflowEvents::POST_GLOBAL_ACTION => 'onPostGlobalAction',
        ];
    }
}
