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

namespace Pimcore\Console;

/**
 * @internal
 */
final class CommandContextHolder
{
    private ?string $commandName = null;

    public function setCommandName(string $commandName): void
    {
        $this->commandName = $commandName;
    }

    public function getCommandName(): ?string
    {
        return $this->commandName;
    }

    public function isCommand(string $expectedCommand): bool
    {
        return $this->commandName === $expectedCommand;
    }
}
