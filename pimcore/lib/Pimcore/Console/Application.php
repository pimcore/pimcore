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

use Pimcore\Version;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\ConsoleEvents;
use Pimcore\Tool\Admin;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The console application
 */
class Application extends \Symfony\Bundle\FrameworkBundle\Console\Application
{
    /**
     * Autoloaded namespaces
     *
     * @var array
     */
    protected $autoloadNamespaces = [];

    /**
     * Constructor.
     *
     * @param string $name The name of the application
     * @param string $version The version of the application
     *
     * @api
     */
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->setName('Pimcore');
        $this->setVersion(Version::getVersion());

        // init default autoload namespaces
        $this->initDefaultAutoloadNamespaces();

        // allow to register commands here (e.g. through plugins)
        \Pimcore::getEventManager()->trigger('system.console.init', $this);

        $dispatcher = new EventDispatcher();
        $this->setDispatcher($dispatcher);

        $dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
            if ($event->getInput()->getOption("maintenance-mode")) {
                // enable maintenance mode if requested
                $maintenanceModeId = 'cache-warming-dummy-session-id';

                $event->getOutput()->writeln('Activating maintenance mode with ID <comment>' . $maintenanceModeId . '</comment> ...');

                Admin::activateMaintenanceMode($maintenanceModeId);
            }
        });

        $dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
            if ($event->getInput()->getOption("maintenance-mode")) {
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
        $inputDefinition->addOption(new InputOption('environment', null, InputOption::VALUE_OPTIONAL, 'Explicitly set the environment, eg. production, dev, stage, ...'));

        return $inputDefinition;
    }

    /**
     * Init default autoload namespaces. More namespaces can be added via addAutoloadNamespace()
     */
    protected function initDefaultAutoloadNamespaces()
    {
        $defaultAutoloadNamespaces = [
            'Pimcore\\Console\\Command' => PIMCORE_DOCUMENT_ROOT . '/pimcore/lib/Pimcore/Console/Command',
            'Website\\Console\\Command' => PIMCORE_DOCUMENT_ROOT . '/website/lib/Website/Console/Command'
        ];

        foreach ($defaultAutoloadNamespaces as $namespace => $directory) {
            $this->addAutoloadNamespace($namespace, $directory);
        }
    }

    /**
     * Add a namespace to autoload commands from
     *
     * @param $namespace
     * @param $directory
     * @return $this
     */
    public function addAutoloadNamespace($namespace, $directory)
    {
        if (isset($this->autoloadNamespaces[$namespace])) {
            throw new \RuntimeException(sprintf('Autoload namespace %s is already defined. Can\'t add it again.', $namespace));
        }

        $this->autoloadNamespaces[$namespace] = $directory;
        foreach ($this->findNamespaceCommands($namespace, $directory) as $className) {
            $this->add(new $className());
        }

        return $this;
    }

    /**
     * Find all commands in a namespace. Commands must extend Symfony\Component\Console\Command\Command and
     * have its name ending in Command (e.g. AwesomeCommand).
     *
     * @param $namespace
     * @param $directory
     * @return array
     */
    public function findNamespaceCommands($namespace, $directory)
    {
        $commands = [];

        if (!(file_exists($directory) && is_dir($directory))) {
            return $commands;
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in($directory)
            ->name('*Command.php');

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $subNamespace = trim(str_replace($directory, '', $file->getPath()), DIRECTORY_SEPARATOR);
            if (!empty($subNamespace)) {
                $subNamespace = str_replace('/', '\\', $subNamespace);
                $subNamespace = '\\' . $subNamespace;
            }

            $class = $namespace . $subNamespace . '\\' . $file->getBasename('.php');
            if (class_exists($class)) {
                $reflector = new \ReflectionClass($class);
                if ($reflector->isInstantiable() && $reflector->isSubclassOf('Symfony\\Component\\Console\\Command\\Command')) {
                    $commands[] = $class;
                }
            }
        }

        return $commands;
    }
}
