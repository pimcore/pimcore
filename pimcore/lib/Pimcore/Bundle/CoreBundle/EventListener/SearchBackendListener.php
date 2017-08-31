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
use Pimcore\Event\DataObjectEvents;
use Pimcore\Model\Search\Backend\Data;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchBackendListener implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectEvents::POST_ADD  => 'onPostAddElement',
            DocumentEvents::POST_ADD  => 'onPostAddElement',
            AssetEvents::POST_ADD  => 'onPostAddElement',

            DataObjectEvents::PRE_DELETE => 'onPreDeleteElement',
            DocumentEvents::PRE_DELETE => 'onPreDeleteElement',
            AssetEvents::PRE_DELETE => 'onPreDeleteElement',

            DataObjectEvents::POST_UPDATE  => 'onPostUpdateElement',
            DocumentEvents::POST_UPDATE  => 'onPostUpdateElement',
            AssetEvents::POST_UPDATE  => 'onPostUpdateElement',
        ];
    }

    /**
     * @param ElementEventInterface $e
     */
    public function onPostAddElement(ElementEventInterface $e)
    {
        $searchEntry = new Data($e->getElement());
        $searchEntry->save();
    }

    /**
     * @param ElementEventInterface $e
     */
    public function onPreDeleteElement(ElementEventInterface $e)
    {
        $searchEntry = Data::getForElement($e->getElement());
        if ($searchEntry instanceof Data and $searchEntry->getId() instanceof Data\Id) {
            $searchEntry->delete();
        }
    }

    /**
     * @param ElementEventInterface $e
     */
    public function onPostUpdateElement(ElementEventInterface $e)
    {
        $element = $e->getElement();
        $searchEntry = Data::getForElement($element);
        if ($searchEntry instanceof Data and $searchEntry->getId() instanceof Data\Id) {
            $searchEntry->setDataFromElement($element);
            $searchEntry->save();
        } else {
            $this->onPostAddElement($e);
        }
    }
}
