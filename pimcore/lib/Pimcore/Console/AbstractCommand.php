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

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Pimcore\Logger;
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
            //throw new \RuntimeException('In maintenance mode - set the flag --ignore-maintenance-mode to force execution!');
        }
    }

    /**
     * Initialize logging
     */
    protected function initializeLogging()
    {
        $logger = $this->getLogger();

        // hook logger into pimcore
        /*
        Logger::addLogger($logger);

        if ($this->output->isVerbose()) {
            Logger::setPriorities([
                "info",
                "notice",
                "warning",
                "error",
                "critical",
                "alert",
                "emergency"
            ]);
        }

        // set all priorities
        if ($this->output->isDebug()) {
            Logger::setVerbosePriorities();
        }
        */
    }

    /**
     *
     */
    protected function disableLogging()
    {
        Logger::removeLogger($this->getLogger());
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
     * @return \Monolog\Logger|LoggerInterface
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

            $logger = new \Monolog\Logger('core');
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
