<?php
declare(strict_types=1);

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
