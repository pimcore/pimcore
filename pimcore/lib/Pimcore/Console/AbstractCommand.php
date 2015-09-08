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

use Pimcore\Console\Log\Writer;
use Pimcore\Tool\Admin;
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->input  = $input;
        $this->output = $output;

        // use Console\Dumper for nice debug output
        $this->dumper = new Dumper($this->output);

        // skip if maintenance mode is on and the flag is not set
        if (Admin::isInMaintenanceMode() && !$input->getOption('ignore-maintenance-mode')) {
            throw new \RuntimeException('In maintenance mode - set the flag --ignore-maintenance-mode to force execution!');
        }
    }

    /**
     * Hook into the pimcore logger using the Pimcore\Console\Log\Writer
     *
     * @param int $filterPriority
     */
    protected function initializePimcoreLogging($filterPriority = \Zend_Log::INFO)
    {
        $writer = new Writer($this->output);
        $logger = new \Zend_Log($writer);

        if ($this->output->isVerbose()) {
            $filterPriority = null;
        }

        if (null !== $filterPriority) {
            $logger->addFilter(new \Zend_Log_Filter_Priority($filterPriority));
        }

        // the filter handles verbosity
        \Logger::setVerbosePriorities();
        \Logger::addLogger($logger);
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
