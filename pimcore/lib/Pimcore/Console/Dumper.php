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
