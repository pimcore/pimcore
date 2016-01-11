<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Console;

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimcore\Console\Log\Formatter\ConsoleColorFormatter;
use Pimcore\Tool\Admin;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base command class setting up some defaults (e.g. the ignore-maintenance-mode switch and the VarDumper component).
 */
abstract class AbstractCommand extends \Symfony\Component\Console\Command\Command
{
    /** @var InputInterface */
    protected $input;

    /** @var ConsoleOutput */
    protected $output;

    /** @var Dumper */
    protected $dumper;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->input  = $input;
        $this->output = $output;

        $this->initializeLogging();

        // use Console\Dumper for nice debug output
        $this->dumper = new Dumper($this->output);

        // skip if maintenance mode is on and the flag is not set
        if (Admin::isInMaintenanceMode() && !$input->getOption('ignore-maintenance-mode')) {
            throw new \RuntimeException('In maintenance mode - set the flag --ignore-maintenance-mode to force execution!');
        }
    }

    /**
     * Initialize logging
     */
    protected function initializeLogging()
    {
        $logger = $this->getLogger();

        // hook logger into pimcore
        \Logger::addLogger($logger);

        // set all priorities
        \Logger::setVerbosePriorities();
    }

    /**
     * Get log level - default to warning, but show all messages in verbose mode
     *
     * @return null|string
     */
    protected function getLogLevel()
    {
        $logLevel = LogLevel::WARNING;
        if ($this->output->isVerbose()) {
            $logLevel = null;
        }

        return $logLevel;
    }

    /**
     * @return Logger|LoggerInterface
     */
    protected function getLogger()
    {
        if (null === $this->logger) {
            $handler = null;
            if ($this->output->isQuiet()) {
                $handler = new NullHandler();
            } else {
                $handler = new StreamHandler($this->output->getStream(), $this->getLogLevel());
                if (!$this->input->getOption('no-ansi')) {
                    $handler->setFormatter(new ConsoleColorFormatter());
                }
            }

            $logger = new Logger('core');
            $logger->pushHandler($handler);

            $this->logger = $logger;
        }

        return $this->logger;
    }

    /**
     * @param mixed $data
     * @param null|int $flags
     */
    protected function dump($data, $flags = null)
    {
        $this->dumper->dump($data, $flags);
    }

    /**
     * @param mixed $data
     * @param null|int $flags
     */
    protected function dumpVerbose($data, $flags = null)
    {
        if ($this->output->isVerbose()) {
            $this->dump($data, $flags);
        }
    }

    /**
     * @param $message
     */
    protected function writeError($message)
    {
        $this->output->writeln(sprintf('<error>ERROR: %s</error>', $message));
    }
}
