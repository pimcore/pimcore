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

namespace Pimcore\Console\Log\Formatter;

use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ConsoleColorFormatter extends LineFormatter
{
    /**
     * @var OutputFormatter
     */
    protected $outputFormatter;

    /**
     * Initialize and get console output formatter
     *
     * @return OutputFormatter
     */
    protected function getOutputFormatter()
    {
        if (null === $this->outputFormatter) {
            $formatter = new OutputFormatter(true);
            $formatter->setStyle(LogLevel::EMERGENCY, new OutputFormatterStyle('white', 'red'));
            $formatter->setStyle(LogLevel::ALERT, new OutputFormatterStyle('white', 'red'));
            $formatter->setStyle(LogLevel::CRITICAL, new OutputFormatterStyle('red'));
            $formatter->setStyle(LogLevel::ERROR, new OutputFormatterStyle('red'));
            $formatter->setStyle(LogLevel::WARNING, new OutputFormatterStyle('yellow'));
            $formatter->setStyle(LogLevel::NOTICE, new OutputFormatterStyle());
            $formatter->setStyle(LogLevel::INFO, new OutputFormatterStyle());
            $formatter->setStyle(LogLevel::DEBUG, new OutputFormatterStyle('cyan'));

            $this->outputFormatter = $formatter;
        }

        return $this->outputFormatter;
    }

    /**
     * Formats a log record.
     *
     * @param  array $record A record to format
     *
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        $formatted = parent::format($record);
        $levelName = strtolower($record['level_name']);
        $wrapped   = sprintf('<%1$s>%2$s</%1$s>', $levelName, $formatted);
        $result    = $this->getOutputFormatter()->format($wrapped);

        return $result;
    }
}
