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

use Pimcore\Event\DataObjectClassificationStoreEvents;
use Pimcore\Event\Model\DataObject\ClassificationStore\GroupConfigEvent;
use Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Invalidates the process-scoped classification store relation cache whenever a key or a group
 * config changes within the same request/message: a key config carries the field definition the
 * cache hands out, and saving one with the enabled flag cleared removes its relations altogether.
 *
 * A relation itself dispatches no event, so it is invalidated from
 * {@see \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao} instead.
 *
 * The per-request/per-message boundary is handled separately by the kernel.reset tag on
 * {@see KeyGroupRelationCache}.
 *
 * @internal
 */
final class KeyGroupRelationCacheInvalidationListener implements EventSubscriberInterface
{
    public function __construct(private readonly KeyGroupRelationCache $keyGroupRelationCache)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectClassificationStoreEvents::KEY_CONFIG_POST_ADD => 'onKeyConfigChange',
            DataObjectClassificationStoreEvents::KEY_CONFIG_POST_UPDATE => 'onKeyConfigChange',
            DataObjectClassificationStoreEvents::KEY_CONFIG_POST_DELETE => 'onKeyConfigChange',
            DataObjectClassificationStoreEvents::GROUP_CONFIG_POST_ADD => 'onGroupConfigChange',
            DataObjectClassificationStoreEvents::GROUP_CONFIG_POST_UPDATE => 'onGroupConfigChange',
            DataObjectClassificationStoreEvents::GROUP_CONFIG_POST_DELETE => 'onGroupConfigChange',
        ];
    }

    public function onKeyConfigChange(KeyConfigEvent $event): void
    {
        $keyId = $event->getKeyConfig()->getId();
        if ($keyId !== null) {
            $this->keyGroupRelationCache->clearKey($keyId);
        }
    }

    public function onGroupConfigChange(GroupConfigEvent $event): void
    {
        $groupId = $event->getGroupConfig()->getId();
        if ($groupId !== null) {
            $this->keyGroupRelationCache->clearGroup($groupId);
        }
    }
}
