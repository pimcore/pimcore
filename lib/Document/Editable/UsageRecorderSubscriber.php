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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Editable;

use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\EditableNameEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final class UsageRecorderSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $recordedEditableNames = [];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::EDITABLE_NAME => 'onBuildEditableName',
        ];
    }

    /**
     * @param EditableNameEvent $event
     */
    public function onBuildEditableName(EditableNameEvent $event)
    {
        $this->recordedEditableNames[] = $event->getEditableName();
    }

    /**
     * @return array
     */
    public function getRecordedEditableNames(): array
    {
        return $this->recordedEditableNames;
    }

    /**
     * @param array $recordedEditableNames
     */
    public function setRecordedEditableNames(array $recordedEditableNames): void
    {
        $this->recordedEditableNames = $recordedEditableNames;
    }
}
