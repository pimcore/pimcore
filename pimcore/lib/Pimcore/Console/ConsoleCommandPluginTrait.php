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

namespace Pimcore\Console;

use Symfony\Component\Console\Command\Command;

trait ConsoleCommandPluginTrait
{
    use CliTrait;

    /**
     * Handle system.console.init event and register console commands to the console application
     *
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function initConsoleCommands()
    {
        if (static::isCli()) {
            \Pimcore::getEventManager()->attach('system.console.init', [$this, 'handleSystemConsoleInitEvent']);
        }
    }

    /**
     * system.console.init event handler
     *
     * @param \Zend_EventManager_Event $e
     */
    public function handleSystemConsoleInitEvent(\Zend_EventManager_Event $e)
    {
        /** @var Application $application */
        $application = $e->getTarget();
        $application->addCommands($this->getConsoleCommands());
    }

    /**
     * Returns an array of commands to be added to the application.
     * To be implemented by plugin classes providing console commands.
     *
     * @return Command[]
     */
    abstract public function getConsoleCommands();
}
