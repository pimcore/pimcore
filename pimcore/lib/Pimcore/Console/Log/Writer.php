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

namespace Pimcore\Console\Log;

use Symfony\Component\Console\Output\OutputInterface;
use Zend_Config;
use Zend_Log_FactoryInterface;

class Writer extends \Zend_Log_Writer_Abstract
{
    /** @var OutputInterface */
    protected $output;

    /**
     * Construct a Zend_Log driver
     *
     * @param  array|Zend_Config $config
     * @return Zend_Log_FactoryInterface
     */
    static public function factory($config)
    {
        return new self($config['output']);
    }

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        // override this by calling setFormatter after initializing the writer
        $this->setFormatter(new \Pimcore\Console\Log\Formatter\Simple());
    }

    /**
     * Write a message to the log.
     *
     * @param  array $event log data event
     * @return void
     */
    protected function _write($event)
    {
        $line = $this->_formatter->format($event);
        $this->output->writeln($line);
    }
}
