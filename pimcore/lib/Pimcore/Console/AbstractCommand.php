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
