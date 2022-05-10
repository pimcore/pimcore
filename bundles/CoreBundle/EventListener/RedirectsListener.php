<?php

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

use Pimcore\Event\RedirectEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Event\Model\RedirectEvent;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

/**
 * @internal
 */
class RedirectsListener implements EventSubscriberInterface
{
    const GONE_STATUS_CODE = 410;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RedirectEvents::PRE_BUILD => 'onPreBuild',
        ];
    }

    /**
     * @param RedirectEvent $e
     */
    public function onPreBuild(RedirectEvent $e)
    {
        if ((int)$e->getRedirect()->getStatusCode() === self::GONE_STATUS_CODE) {
            throw new GoneHttpException("The resource no longer exists.");
        }
    }

}
