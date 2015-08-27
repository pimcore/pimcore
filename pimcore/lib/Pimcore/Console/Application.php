<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Console;

use Pimcore\Version;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * The console application
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * Namespaces to automatically scan for commands which can be autoloaded. More namespaces
     * can be added via addAutoloadNamespace()
     *
     * @var array
     */
    protected $defaultAutoloadNamespaces = [
        'Pimcore\\Console\\Command' => PIMCORE_DOCUMENT_ROOT . '/pimcore/lib/Pimcore/Console/Command',
        'Website\\Console\\Command' => PIMCORE_DOCUMENT_ROOT . '/website/lib/Website/Console/Command'
    ];

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
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct('Pimcore CLI', Version::getVersion());

        foreach ($this->defaultAutoloadNamespaces as $namespace => $directory) {
            $this->addAutoloadNamespace($namespace, $directory);
        }

        // allow to register commands here (e.g. through plugins)
        \Pimcore::getEventManager()->trigger('system.console.init', $this);
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

        return $inputDefinition;
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
            $subNamespace = trim(str_replace($directory, '', $file->getPath()), '/');
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
