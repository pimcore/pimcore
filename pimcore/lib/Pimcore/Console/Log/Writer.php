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
