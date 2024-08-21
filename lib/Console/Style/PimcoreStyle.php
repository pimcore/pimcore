<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Console\Style;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class PimcoreStyle extends SymfonyStyle
{
    private InputInterface $input;

    private OutputInterface $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        parent::__construct($input, $output);
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * Prints an underlined title without prepending block and/or formatting output
     *
     */
    public function simpleSection(string $message, string $underlineChar = '-', string $style = null): void
    {
        $underline = str_repeat($underlineChar, Helper::width(Helper::removeDecoration($this->getFormatter(), $message)));

        if (null !== $style) {
            $format = '<%s>%s</>';
            $message = sprintf($format, $style, $message);
            $underline = sprintf($format, $style, $underline);
        }

        $this->writeln([
            '',
            $message,
            $underline,
            '',
        ]);
    }
}
