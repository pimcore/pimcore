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

namespace Pimcore\Console;

use Pimcore\Event\System\ConsoleEvent;
use Pimcore\Event\SystemEvents;
use Pimcore\Tool\Admin;
use Pimcore\Version;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The console application
 */
class Application extends \Symfony\Bundle\FrameworkBundle\Console\Application
{
    /**
     * Constructor.
     *
     * @param KernelInterface $kernel
     *
     * @internal param string $name The name of the application
     * @internal param string $version The version of the application
     *
     * @api
     */
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->setName('Pimcore');
        $this->setVersion(Version::getVersion());

        // allow to register commands here (e.g. through plugins)
        $dispatcher = \Pimcore::getEventDispatcher();
        $event = new ConsoleEvent($this);
        $dispatcher->dispatch(SystemEvents::CONSOLE_INIT, $event);

        $this->setDispatcher($dispatcher);

        $dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
            if ($event->getInput()->getOption('no-debug') && \Pimcore::inDebugMode()) {
                \Pimcore::setDebugMode(false);
            }

            if ($event->getInput()->getOption('maintenance-mode')) {
                // enable maintenance mode if requested
                $maintenanceModeId = 'cache-warming-dummy-session-id';

                $event->getOutput()->writeln('Activating maintenance mode with ID <comment>' . $maintenanceModeId . '</comment> ...');

                Admin::activateMaintenanceMode($maintenanceModeId);
            }
        });

        $dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
            if ($event->getInput()->getOption('maintenance-mode')) {
                $event->getOutput()->writeln('Deactivating maintenance mode...');
                Admin::deactivateMaintenanceMode();
            }
        });
    }

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        $inputDefinition = parent::getDefaultInputDefinition();
        $inputDefinition->addOption(new InputOption('ignore-maintenance-mode', null, InputOption::VALUE_NONE, 'Set this flag to force execution in maintenance mode'));
        $inputDefinition->addOption(new InputOption('maintenance-mode', null, InputOption::VALUE_NONE, 'Set this flag to force maintenance mode while this task runs'));

        return $inputDefinition;
    }
}
