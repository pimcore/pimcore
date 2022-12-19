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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Config;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectClassDefinitionEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Event\Model\ElementEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
class UUIDListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
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

    public function onPostAdd(Event $e)
    {
        if ($this->isEnabled()) {
            $element = $this->extractElement($e);

            if ($element) {
                \Pimcore\Model\Tool\UUID::create($element);
            }
        }
    }

    public function onPostDelete(Event $e)
    {
        if ($this->isEnabled()) {
            $element = $this->extractElement($e);

            if ($element) {
                $uuidObject = \Pimcore\Model\Tool\UUID::getByItem($element);
                if ($uuidObject instanceof \Pimcore\Model\Tool\UUID) {
                    $uuidObject->delete();
                }
            }
        }
    }

    protected function isEnabled(): bool
    {
        $config = Config::getSystemConfiguration('general');
        if (!empty($config['instance_identifier'])) {
            return true;
        }

        return false;
    }

    protected function extractElement(Event $event): \Pimcore\Model\DataObject\ClassDefinition|\Pimcore\Model\Element\ElementInterface|null
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
