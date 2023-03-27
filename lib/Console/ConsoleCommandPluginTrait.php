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

namespace Pimcore\Console;

use Pimcore\Event\System\ConsoleEvent;
use Pimcore\Event\SystemEvents;
use Symfony\Component\Console\Command\Command;

trigger_deprecation('pimcore/pimcore', '10.6', 'The "%s" trait is deprecated and will be removed in Pimcore 11.', ConsoleCommandPluginTrait::class);

/**
 * @deprecated since Pimcore 10.6 and will be removed in Pimcore 11
 */
trait ConsoleCommandPluginTrait
{
    /**
     * Handle system.console.init event and register console commands to the console application
     */
    public function initConsoleCommands(): void
    {
        if (static::isCli()) {
            \Pimcore::getEventDispatcher()->addListener(SystemEvents::CONSOLE_INIT, [$this, 'handleSystemConsoleInitEvent']);
        }
    }

    public function handleSystemConsoleInitEvent(ConsoleEvent $e): void
    {
        $application = $e->getApplication();
        $application->addCommands($this->getConsoleCommands());
    }

    public static function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Returns an array of commands to be added to the application.
     * To be implemented by plugin classes providing console commands.
     *
     * @return Command[]
     */
    abstract public function getConsoleCommands(): array;
}
