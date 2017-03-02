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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\ObjectClassDefinitionEvents;
use Pimcore\Event\ObjectEvents;
use Pimcore\Model\Element\Service;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UUIDListener implements EventSubscriberInterface {

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ObjectEvents::POST_ADD  => 'onPostAdd',
            DocumentEvents::POST_ADD  => 'onPostAdd',
            AssetEvents::POST_ADD  => 'onPostAdd',
            ObjectClassDefinitionEvents::POST_ADD => "onPostAdd",

            ObjectEvents::POST_DELETE => 'onPostDelete',
            DocumentEvents::POST_DELETE => 'onPostDelete',
            AssetEvents::POST_DELETE => 'onPostDelete',
            ObjectClassDefinitionEvents::POST_DELETE => "onPostDelete"
        ];
    }

    /**
     * @param ElementEventInterface $e
     */
    public function onPostAdd(ElementEventInterface $e) {
        \Pimcore\Model\Tool\UUID::create($e->getElement());
    }

    /**
     * @param ElementEventInterface $e
     */
    public function onPostDelete(ElementEventInterface $e) {
        $uuidObject = \Pimcore\Model\Tool\UUID::getByItem($e->getElement());
        if ($uuidObject instanceof \Pimcore\Model\Tool\UUID) {
            $uuidObject->delete();
        }
    }
}