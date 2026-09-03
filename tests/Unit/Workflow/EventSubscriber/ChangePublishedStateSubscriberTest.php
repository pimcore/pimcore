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

namespace Pimcore\Tests\Unit\Workflow\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Pimcore\Event\Workflow\GlobalActionEvent;
use Pimcore\Event\WorkflowEvents;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\GlobalAction;
use stdClass;
use Symfony\Component\Workflow\WorkflowInterface;

final class ChangePublishedStateSubscriberTest extends TestCase
{
    public function testItSubscribesToThePostGlobalActionEvent(): void
    {
        $this->assertArrayHasKey(
            WorkflowEvents::POST_GLOBAL_ACTION,
            ChangePublishedStateSubscriber::getSubscribedEvents()
        );
    }

    public function testGlobalActionForcesTheSubjectToBePublished(): void
    {
        $subject = new Concrete();
        $subject->setPublished(false);

        (new ChangePublishedStateSubscriber())->onPostGlobalAction(
            $this->createEvent($subject, ChangePublishedStateSubscriber::FORCE_PUBLISHED)
        );

        $this->assertTrue($subject->isPublished());
    }

    public function testGlobalActionForcesTheSubjectToBeUnpublished(): void
    {
        $subject = new Concrete();
        $subject->setPublished(true);

        (new ChangePublishedStateSubscriber())->onPostGlobalAction(
            $this->createEvent($subject, ChangePublishedStateSubscriber::FORCE_UNPUBLISHED)
        );

        $this->assertFalse($subject->isPublished());
    }

    public function testGlobalActionLeavesThePublishedStateUntouchedByDefault(): void
    {
        $subject = new Concrete();
        $subject->setPublished(true);

        (new ChangePublishedStateSubscriber())->onPostGlobalAction($this->createEvent($subject, null));

        $this->assertTrue($subject->isPublished());
    }

    public function testGlobalActionIgnoresSubjectsWithoutAPublishedState(): void
    {
        $subject = new stdClass();

        (new ChangePublishedStateSubscriber())->onPostGlobalAction(
            $this->createEvent($subject, ChangePublishedStateSubscriber::FORCE_UNPUBLISHED)
        );

        // nothing to assert on the subject itself, the subscriber must simply not fail
        $this->assertObjectNotHasProperty('published', $subject);
    }

    private function createEvent(object $subject, ?string $changePublishedState): GlobalActionEvent
    {
        $options = $changePublishedState === null ? [] : ['changePublishedState' => $changePublishedState];

        $globalAction = new GlobalAction(
            'myGlobalAction',
            $options,
            $this->createStub(ExpressionService::class),
            'myWorkflow'
        );

        return new GlobalActionEvent($this->createStub(WorkflowInterface::class), $subject, $globalAction);
    }
}
