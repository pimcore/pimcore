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

use Pimcore\Console\CommandContextHolder;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class ConsoleCommandListener implements EventSubscriberInterface
{
    private CommandContextHolder $contextHolder;

    public function __construct(CommandContextHolder $contextHolder)
    {
        $this->contextHolder = $contextHolder;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleCommandEvent::class => 'onConsoleCommand',
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command) {
            $this->contextHolder->setCommandName($command->getName());
        }
    }
}
