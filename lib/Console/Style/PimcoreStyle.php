<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
    public function simpleSection(string $message, string $underlineChar = '-', ?string $style = null): void
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
