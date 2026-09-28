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

namespace Pimcore\Tests\Unit\Model\Workflow\EventSubscriber;

use ErrorException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\Transition;
use ReflectionMethod;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotesSubscriberTest extends TestCase
{
    /**
     * Regression: getAdditionalDataForField() compared checkbox values with a strict
     * `=== 'true'` string check. Callers that hydrate additional-field values as native
     * booleans (rather than the legacy form-encoded strings) send a real `true`, which never
     * matched the string comparison, so a checked "required" checkbox was always read back
     * as false and the transition could never be submitted.
     */
    public function testCheckboxFieldAcceptsNativeBooleanTrue(): void
    {
        $result = $this->getAdditionalDataForCheckbox(true);

        $this->assertTrue($result);
    }

    public function testCheckboxFieldAcceptsNativeBooleanFalse(): void
    {
        $result = $this->getAdditionalDataForCheckbox(false);

        $this->assertFalse($result);
    }

    public function testCheckboxFieldStillAcceptsLegacyStringTrue(): void
    {
        $result = $this->getAdditionalDataForCheckbox('true');

        $this->assertTrue($result);
    }

    public function testCheckboxFieldStillAcceptsLegacyStringFalse(): void
    {
        $result = $this->getAdditionalDataForCheckbox('false');

        $this->assertFalse($result);
    }

    /**
     * Regression: a configured additional field the client did not submit was read with an
     * unguarded array access, so resolving it raised an "Undefined array key" diagnostic
     * instead of resolving to an unchecked checkbox.
     */
    public function testAbsentCheckboxFieldIsReadAsUnchecked(): void
    {
        $result = $this->getAdditionalDataForField(
            [
                'name' => 'marketingInvolved',
                'fieldType' => 'checkbox',
                'title' => 'Please confirm that Marketing was sufficiently involved',
                'required' => true,
            ],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Same unguarded array access, for every other field type: an absent field resolves to
     * null, which the required-field check in handleNotesPreWorkflow() reads as "not filled in".
     */
    public function testAbsentFieldOfAnyOtherTypeIsReadAsNull(): void
    {
        $result = $this->getAdditionalDataForField(
            [
                'name' => 'dateLastContacted',
                'fieldType' => 'date',
                'title' => 'Date of Conversation',
                'required' => false,
            ],
            []
        );

        $this->assertNull($result);
    }

    /**
     * The user-facing consequence: submitting a transition without a required additional field
     * must fail with the translated validation message, not with a PHP diagnostic escalated by
     * the debug error handler.
     */
    public function testRequiredFieldAbsentFromSubmittedDataFailsValidation(): void
    {
        $subscriber = $this->createSubscriber([]);

        $transition = new Transition('close', 'open', 'closed', [
            'notes' => [
                'additionalFields' => [
                    [
                        'name' => 'marketingInvolved',
                        'fieldType' => 'checkbox',
                        'title' => 'Please confirm that Marketing was sufficiently involved',
                        'required' => true,
                    ],
                ],
            ],
        ]);

        $event = new Event($this->createStub(ElementInterface::class), new Marking(), $transition);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('workflow_notes_requred_field_message');

        $this->withStrictErrorHandler(static function () use ($subscriber, $event): void {
            $subscriber->onWorkflowEnter($event);
        });
    }

    private function getAdditionalDataForCheckbox(mixed $rawValue): mixed
    {
        return $this->getAdditionalDataForField(
            [
                'name' => 'marketingInvolved',
                'fieldType' => 'checkbox',
                'title' => 'Please confirm that Marketing was sufficiently involved',
                'required' => true,
            ],
            ['marketingInvolved' => $rawValue]
        );
    }

    /**
     * @param array<string, mixed> $fieldConfig
     * @param array<string, mixed> $submittedFields
     */
    private function getAdditionalDataForField(array $fieldConfig, array $submittedFields): mixed
    {
        $subscriber = $this->createSubscriber($submittedFields);

        $method = new ReflectionMethod($subscriber, 'getAdditionalDataForField');

        return $this->withStrictErrorHandler(static function () use ($method, $subscriber, $fieldConfig): mixed {
            return $method->invoke($subscriber, $fieldConfig);
        });
    }

    /**
     * @param array<string, mixed> $submittedFields
     */
    private function createSubscriber(array $submittedFields): NotesSubscriber
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $subscriber = new NotesSubscriber($translator);
        $subscriber->setAdditionalData(['additional' => $submittedFields]);

        return $subscriber;
    }

    /**
     * Promotes any PHP diagnostic raised inside the callback into an exception, the way the
     * debug error handler does at runtime, so a stray "Undefined array key" warning fails the
     * test instead of silently resolving to null.
     *
     * @throws ErrorException
     */
    private function withStrictErrorHandler(callable $callback): mixed
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
