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

namespace Pimcore\Document\Tag;

use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\TagNameEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UsageRecorderSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $recordedTagNames = [];

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::TAG_NAME => 'onBuildTagName'
        ];
    }

    public function onBuildTagName(TagNameEvent $event)
    {
        if (null === $document = $event->getDocument()) {
            throw new \RuntimeException('Need a document to migrate tag naming strategy.');
        }

        $this->recordedTagNames[] = $event->getTagName();
    }

    /**
     * @return array
     */
    public function getRecordedTagNames(): array
    {
        return $this->recordedTagNames;
    }

    /**
     * @param array $recordedTagNames
     */
    public function setRecordedTagNames(array $recordedTagNames): void
    {
        $this->recordedTagNames = $recordedTagNames;
    }
}
