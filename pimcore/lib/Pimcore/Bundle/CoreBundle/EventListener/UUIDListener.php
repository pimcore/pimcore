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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Event\ObjectClassDefinitionEvents;
use Pimcore\Event\ObjectEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UUIDListener implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ObjectEvents::POST_ADD  => 'onPostAdd',
            DocumentEvents::POST_ADD  => 'onPostAdd',
            AssetEvents::POST_ADD  => 'onPostAdd',
            ObjectClassDefinitionEvents::POST_ADD => 'onPostAdd',

            ObjectEvents::POST_DELETE => 'onPostDelete',
            DocumentEvents::POST_DELETE => 'onPostDelete',
            AssetEvents::POST_DELETE => 'onPostDelete',
            ObjectClassDefinitionEvents::POST_DELETE => 'onPostDelete'
        ];
    }

    /**
     * @param Event $e
     */
    public function onPostAdd(Event $e)
    {
        if ($this->isEnabled()) {
            $element = $this->extractElement($e);

            if ($element) {
                \Pimcore\Model\Tool\UUID::create($element);
            }
        }
    }

    /**
     * @param Event $e
     */
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

    /**
     * @return bool
     */
    protected function isEnabled()
    {
        $conf = \Pimcore\Config::getSystemConfig();
        if ($conf->general->instanceIdentifier) {
            return true;
        }

        return false;
    }

    /**
     * @param Event $event
     *
     * @return null|\Pimcore\Model\Element\ElementInterface|\Pimcore\Model\DataObject\ClassDefinition
     */
    protected function extractElement(Event $event)
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
