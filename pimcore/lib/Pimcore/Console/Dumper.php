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

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Helper class to use the Symfony\VarDumper component from CLI commands
 */
class Dumper
{
    const NEWLINE_BEFORE = 1;
    const NEWLINE_AFTER  = 2;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var CliDumper
     */
    protected $cliDumper;

    /**
     * @var VarCloner
     */
    protected $varCloner;

    /**
     * @param ConsoleOutput $output
     * @param CliDumper $cliDumper
     * @param VarCloner $varCloner
     */
    public function __construct(ConsoleOutput $output, CliDumper $cliDumper = null, VarCloner $varCloner = null)
    {
        $this->output = $output;
        $this->setCliDumper($cliDumper);
        $this->setVarCloner($varCloner);
    }

    /**
     * @param CliDumper $cliDumper
     */
    public function setCliDumper(CliDumper $cliDumper = null)
    {
        if (null === $cliDumper) {
            $this->cliDumper = new CliDumper();
        }

        $this->cliDumper->setOutput($this->output->getStream());
    }

    /**
     * @param VarCloner $varCloner
     */
    public function setVarCloner(VarCloner $varCloner = null)
    {
        if (null === $varCloner) {
            $this->varCloner = new VarCloner();
        }
    }

    /**
     * @param $data
     * @param null|int $flags
     */
    public function dump($data, $flags = null)
    {
        if ($flags !== null) {
            if ($flags & self::NEWLINE_BEFORE) {
                $this->output->writeln('');
            }
        }

        $this->cliDumper->dump($this->varCloner->cloneVar($data));

        if ($flags !== null) {
            if ($flags & self::NEWLINE_AFTER) {
                $this->output->writeln('');
            }
        }
    }
}
