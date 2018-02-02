<?php

declare(strict_types=1);

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

namespace Pimcore\Process;

class PartsBuilder
{
    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @var array
     */
    private $options = [];

    public function __construct(array $arguments = [], array $options = [])
    {
        $this->addArguments($arguments);
        $this->addOptions($options);
    }

    public function addArguments(array $arguments)
    {
        foreach ($arguments as $argument) {
            $this->addArgument($argument);
        }
    }

    public function addArgument($argument)
    {
        if (!empty($argument)) {
            $this->arguments[] = $argument;
        }
    }

    public function addOptions(array $options)
    {
        foreach ($options as $option => $value) {
            $this->addOption($option, $value);
        }
    }

    public function addOption(string $option, $value = null)
    {
        if (empty($option)) {
            return;
        }

        // do not set null values
        if (null === $value) {
            return;
        }

        // do not set option if it is false
        if (is_bool($value) && !$value) {
            return;
        }

        $part = '';
        if (1 === strlen($option)) {
            $part = '-' . $option;
        } else {
            $part = '--' . $option;
        }

        if (!is_bool($value) && $value) {
            $part .= '=' . $value;
        }

        $this->options[] = $part;
    }

    public function getParts(): array
    {
        return array_merge($this->arguments, $this->options);
    }
}
