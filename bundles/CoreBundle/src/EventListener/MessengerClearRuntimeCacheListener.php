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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\Service as ClassificationstoreService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

/**
 * Drops the process-scoped caches a worker would otherwise keep for the whole of its life, so that
 * every message is handled against the current configuration: the runtime cache, and the
 * classification store key configs and field definitions, which are held in statics of their own.
 * Saving or deleting a key config drops that key config from {@see DefinitionCache}, but nothing
 * invalidates the definitions {@see ClassificationstoreService::$definitionsCache} memoizes on a
 * write: this listener is the only thing that clears them, at the message boundary.
 *
 * The container-managed caches are dropped between two messages by the kernel.reset tag instead -
 * see {@see \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache}.
 *
 * @internal
 */
class MessengerClearRuntimeCacheListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => 'handle',
        ];
    }

    public function handle(WorkerMessageReceivedEvent $event): void
    {
        RuntimeCache::clear();
        DefinitionCache::clear(null);
        ClassificationstoreService::clearDefinitionsCache();
    }
}
