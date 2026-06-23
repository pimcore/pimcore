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

namespace Pimcore\Document\Editable;

use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\EditableNameEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final class UsageRecorderSubscriber implements EventSubscriberInterface
{
    protected array $recordedEditableNames = [];

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::EDITABLE_NAME => 'onBuildEditableName',
        ];
    }

    public function onBuildEditableName(EditableNameEvent $event): void
    {
        $this->recordedEditableNames[] = $event->getEditableName();
    }

    public function getRecordedEditableNames(): array
    {
        return $this->recordedEditableNames;
    }

    public function setRecordedEditableNames(array $recordedEditableNames): void
    {
        $this->recordedEditableNames = $recordedEditableNames;
    }
}
