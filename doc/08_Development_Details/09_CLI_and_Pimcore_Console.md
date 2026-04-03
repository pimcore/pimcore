---
title: CLI and Pimcore Console
description: Use and create console commands with Pimcore's Symfony Console integration.
---

# CLI and Pimcore Console

Pimcore implements the `Symfony\Console` component and provides `bin/console` as the single entry point to all
console commands. List every available command with:

```bash
bin/console list
```

## Implementing Custom Commands

Create command classes that extend `Pimcore\Console\AbstractCommand` to inherit Pimcore-specific defaults
(maintenance-mode handling, VarDumper helpers, styled output methods). For general information on writing
Symfony console commands, refer to the
[Symfony Console documentation](https://symfony.com/doc/current/console.html).

## Registering Commands

Register commands as services tagged with `console.command`. The default `services.yaml` shipped with the
Pimcore skeleton already enables autoconfiguration for the `App` namespace, so any class extending
`AbstractCommand` is registered automatically.

## Built-in Helpers

`Pimcore\Console\AbstractCommand` adds several helpers to every command.

### `--ignore-maintenance-mode`

The console application adds this option to all commands implicitly. Pimcore checks for it on startup and
prevents execution when the system is in maintenance mode unless the flag is set.

### `--maintenance-mode`

Also added implicitly to all commands. When set, Pimcore activates maintenance mode for the duration of
the command and deactivates it once the command finishes.

### `dump()` and `dumpVerbose()`

Wrappers around the Symfony
[VarDumper component](https://symfony.com/doc/current/components/var_dumper.html).
`dump()` prints output unconditionally; `dumpVerbose()` only prints when the command runs in verbose mode
(`-v`).

## Example

```php
<?php

namespace App\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'awesome:command',
    description: 'Awesome command'
)]
class AwesomeCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dump("Isn't that awesome?");
        $this->dumpVerbose("Only visible with -v");

        // Styled output helpers
        $this->writeError('oh noes!');       // white text on red background
        $this->writeInfo('info');            // green text
        $this->writeComment('comment');      // blue text
        $this->writeQuestion('question');    // yellow text

        return self::SUCCESS;
    }
}
```

## Running Commands

List all registered commands and run a specific one:

```bash
bin/console list
bin/console awesome:command
```

Run the console as the PHP process user (e.g. `www-data` on Debian) to avoid file-permission mismatches:

```bash
su -l www-data -s /bin/bash -c "bin/console awesome:command"
```

## Common Pimcore Commands

### Cache

| Command | Purpose |
|---|---|
| `pimcore:cache:clear` | Clear the Pimcore application cache |
| `pimcore:cache:warming` | Warm up the Pimcore cache |

### Deployment

| Command | Purpose |
|---|---|
| `pimcore:deployment:classes-rebuild` | Rebuild class definitions from JSON files |

### Maintenance

| Command | Purpose |
|---|---|
| `pimcore:maintenance` | Run the Pimcore maintenance job |
| `pimcore:maintenance-mode` | Enable or disable maintenance mode |

### Bundles

| Command | Purpose |
|---|---|
| `pimcore:bundle:install` | Install a Pimcore bundle |
| `pimcore:bundle:list` | List all registered Pimcore bundles |

Run any command with `--help` for a full description of its options and arguments.
