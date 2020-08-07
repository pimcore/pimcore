<?php

declare(strict_types=1);

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

namespace Pimcore\Document\Editable;

use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\TagNameEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UsageRecorderSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     *
     * @deprecated since 6.7 and will be removed in 7. use $recordedEditableNames instead.
     */
    protected $recordedTagNames = [];

    /**
     * @var array
     */
    protected $recordedEditableNames = [];

    public function __construct()
    {
        $this->recordedTagNames = & $this->recordedEditableNames;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::TAG_NAME => 'onBuildTagName',
            DocumentEvents::EDITABLE_NAME => 'onBuildEditableName',
        ];
    }

    /**
     * @param TagNameEvent $event
     *
     */
    public function onBuildEditableName(TagNameEvent $event)
    {
        if (null === $document = $event->getDocument()) {
            throw new \RuntimeException('Need a document to migrate editable naming strategy.');
        }

        $this->recordedEditableNames[] = $event->getTagName();
    }

    /**
     * @param TagNameEvent $event
     *
     * @deprecated since 6.7 and will be removed in 7. use onBuildEditableName() instead.
     */
    public function onBuildTagName(TagNameEvent $event)
    {
        $this->onBuildEditableName($event);
    }

    /**
     * @return array
     *
     * @deprecated since 6.7 and will be removed in 7. use getRecordedEditableNames() instead.
     */
    public function getRecordedTagNames(): array
    {
        return $this->getRecordedEditableNames();
    }

    /**
     * @param array $recordedTagNames
     *
     * @deprecated since 6.7 and will be removed in 7. use setRecordedEditableNames() instead.
     */
    public function setRecordedTagNames(array $recordedTagNames): void
    {
        $this->setRecordedEditableNames($recordedTagNames);
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

class_alias(UsageRecorderSubscriber::class, 'Pimcore\Document\Tag\UsageRecorderSubscriber');
