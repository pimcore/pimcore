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

namespace Pimcore\Workflow\EventSubscriber;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Workflow\Manager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * @internal
 */
class ChangePublishedStateSubscriber implements EventSubscriberInterface
{
    const NO_CHANGE = 'no_change';

    const FORCE_PUBLISHED = 'force_published';

    const FORCE_UNPUBLISHED = 'force_unpublished';

    const SAVE_VERSION = 'save_version';

    public function __construct(private readonly Manager $workflowManager)
    {
    }

    public function onWorkflowCompleted(Event $event): void
    {
        $transition = $event->getTransition();
        $subject = $event->getSubject();

        // only documents and data objects have a published state
        if ($transition === null || (!$subject instanceof Concrete && !$subject instanceof Document)) {
            return;
        }

        $changePublishedState = $this->workflowManager->getChangePublishedState(
            $event->getWorkflowName(),
            $transition
        );

        $this->workflowManager->applyChangePublishedState($subject, $changePublishedState);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.completed' => 'onWorkflowCompleted',
        ];
    }
}
