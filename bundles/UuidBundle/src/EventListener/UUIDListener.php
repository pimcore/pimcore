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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\UuidBundle\EventListener;

use Pimcore;
use Pimcore\Bundle\UuidBundle\Model\Tool\UUID;
use Pimcore\Bundle\UuidBundle\PimcoreUuidBundle;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectClassDefinitionEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\DataObject\ClassDefinitionInterface;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
class UUIDListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_ADD => 'onPostAdd',
            DocumentEvents::POST_ADD => 'onPostAdd',
            AssetEvents::POST_ADD => 'onPostAdd',
            DataObjectClassDefinitionEvents::POST_ADD => 'onPostAdd',

            DataObjectEvents::POST_DELETE => 'onPostDelete',
            DocumentEvents::POST_DELETE => 'onPostDelete',
            AssetEvents::POST_DELETE => 'onPostDelete',
            DataObjectClassDefinitionEvents::POST_DELETE => 'onPostDelete',
        ];
    }

    public function onPostAdd(Event $e): void
    {
        if ($this->isEnabled()) {
            $element = $this->extractElement($e);

            if ($element) {
                UUID::create($element);
            }
        }
    }

    public function onPostDelete(Event $e): void
    {
        if ($this->isEnabled()) {
            $element = $this->extractElement($e);

            if ($element) {
                $uuidObject = UUID::getByItem($element);
                $uuidObject->delete();
            }
        }
    }

    protected function isEnabled(): bool
    {
        if (!PimcoreUuidBundle::isInstalled()) {
            return false;
        }

        $config = Pimcore::getKernel()->getContainer()->getParameter('pimcore_uuid.instance_identifier');
        if (!empty($config)) {
            return true;
        }

        return false;
    }

    protected function extractElement(Event $event): ClassDefinitionInterface|ElementInterface|null
    {
        $element = null;

        if ($event instanceof ElementEventInterface) {
            $element = $event->getElement();
        }

        if ($event instanceof ClassDefinitionEvent) {
            $element = $event->getClassDefinition();
        }

        return $element;
    }
}
