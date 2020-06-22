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

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Workflow\Transition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class ChangePublishedStateSubscriber implements EventSubscriberInterface
{
    const NO_CHANGE = 'no_change';
    const FORCE_PUBLISHED = 'force_published';
    const FORCE_UNPUBLISHED = 'force_unpublished';
    const SAVE_VERSION = 'save_version';

    public function onWorkflowCompleted(Event $event)
    {
        if (!$this->checkEvent($event)) {
            return;
        }

        /** @var Transition $transition */
        $transition = $event->getTransition();

        /** @var Document|Concrete $subject */
        $subject = $event->getSubject();

        $changePublishedState = $transition->getChangePublishedState();

        if ($changePublishedState === self::FORCE_UNPUBLISHED) {
            $subject->setPublished(false);
        } elseif ($changePublishedState === self::FORCE_PUBLISHED) {
            $subject->setPublished(true);
        }
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
        return $event->getTransition() instanceof Transition
            && ($event->getSubject() instanceof Concrete || $event->getSubject() instanceof Document);
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.completed' => 'onWorkflowCompleted',
        ];
    }
}
