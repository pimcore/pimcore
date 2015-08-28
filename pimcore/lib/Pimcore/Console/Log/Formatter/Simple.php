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

namespace Pimcore\Console\Log\Formatter;

class Simple extends \Zend_Log_Formatter_Simple
{
    const DEFAULT_FORMAT = '%lineStyleStart%%timestampStyleStart%%timestamp%%timestampStyleEnd% %priorityStyleStart%%priorityName% (%priority%)%priorityStyleEnd%: %messageStyleStart%%message%%messageStyleEnd%%lineStyleEnd%';

    /**
     * Class constructor
     *
     * @param  null|string  $format  Format specifier for log messages
     * @throws \Zend_Log_Exception
     */
    public function __construct($format = null)
    {
        if (null === $format) {
            $format = static::DEFAULT_FORMAT;
        }

        parent::__construct($format);
    }

    /**
     * Formats data into a single line to be written by the writer.
     *
     * @param  array $event event data
     * @return string             formatted line to write to the log
     */
    public function format($event)
    {
        $types  = ['line', 'timestamp', 'priority', 'message'];
        $styles = [];

        switch ($event['priority']) {
            case \Zend_Log::EMERG:
                $styles['line'] = 'fg=white;bg=red;options=underscore';
                break;
            case \Zend_Log::ALERT:
                $styles['line'] = 'fg=white;bg=red';
                break;

            case \Zend_Log::CRIT:
                $styles['line']     = 'fg=red';
                $styles['priority'] = 'fg=white;bg=red';
                break;

            case \Zend_Log::ERR:
                $styles['timestamp'] = 'fg=red';
                $styles['priority']  = 'fg=white;bg=red';
                break;

            case \Zend_Log::WARN:
                $styles['timestamp'] = 'fg=yellow';
                $styles['priority']  = 'bg=yellow;fg=black';
                break;

            case \Zend_Log::NOTICE:
                $styles['timestamp'] = 'fg=blue';
                $styles['priority']  = 'fg=blue';
                break;

            case \Zend_Log::DEBUG:
                $styles['timestamp'] = 'fg=cyan';
                $styles['priority']  = 'fg=black;bg=cyan';
                break;
        }

        foreach ($types as $type) {
            if (isset($styles[$type])) {
                $event[$type . 'StyleStart'] = sprintf('<%s>', $styles[$type]);
                $event[$type . 'StyleEnd']   = '</>';
            } else {
                $event[$type . 'StyleStart'] = '';
                $event[$type . 'StyleEnd']   = '';
            }
        }

        return parent::format($event);
    }
}
