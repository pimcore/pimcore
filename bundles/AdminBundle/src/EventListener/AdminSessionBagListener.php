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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Config;
use Pimcore\Session\Attribute\LockableAttributeBag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class AdminSessionBagListener implements EventSubscriberInterface
{
    public function __construct(protected Config $config)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            //run after Symfony\Component\HttpKernel\EventListener\SessionListener
            KernelEvents::REQUEST => ['onKernelRequest', 127],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $session = $event->getRequest()->getSession();

        //do not register bags, if session is already started
        if ($session->isStarted()) {
            return;
        }

        $this->configure($session);
    }

    public function configure(SessionInterface $session): void
    {
        foreach ($this->config['session']['attribute_bags'] as $name => $config) {
            $bag = new LockableAttributeBag($config['storage_key']);
            $bag->setName($name);

            $session->registerBag($bag);
        }
    }
}
