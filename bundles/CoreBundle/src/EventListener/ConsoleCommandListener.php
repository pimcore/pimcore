<?php
declare(strict_types=1);

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
