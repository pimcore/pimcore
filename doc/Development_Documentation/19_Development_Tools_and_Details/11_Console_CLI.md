# CLI and Pimcore Console

Pimcore can be executed headless and has a very powerful PHP API. As a consequence of these two aspects, 
it is possible to automate pretty every task within Pimcore. 

Pimcore implements the `Symfony\Console` component and provides `bin/console` as single 
entry point to console commands registered to the `Symfony\Console` application. 


### Implementing own Commands
Have a look at the `Symfony\Console` [documentation](http://symfony.com/doc/3.4/console.html) 
for details how commands are implemented. However, it makes sense to let your command classes extend 
`Pimcore\Console\AbstractCommand` to get some defaults like the `--ignore-maintenance-mode` option 
and a helper for the [Symfony VarDumper Component](http://symfony.com/doc/3.4/components/var_dumper/index.html) 
set up automatically (see below).

### Registering Commands
Commands are defined in classes which must be created in the Command namespace of your bundle (e.g. AppBundle\Command) 
and their names must end with the Command suffix.

To have your command autoloaded, it must match a couple of prerequisites:

* It must be placed in the namespace `Vendor\Command`. 
(e.g. `AppBundle\Command\AwesomeCommand` in `/src/AppBundle/Command/AwesomeCommand.php`)
* The class name must end with `Command`, e.g. `AwesomeCommand`
* The class must inherit `Symfony\Component\Console\Command\Command`, ideally you achieve this by 
extending `Pimcore\Console\AbstractCommand`


### Helpers provided by `Pimcore\Console\AbstractCommand`
The `AbstractCommand` base class provides helpers which make your life easier.

##### `--ignore-maintenance-mode`
The console application implicitly adds the `--ignore-maintenance-mode` option found in other scripts.
`AbstractConsoleCommand` checks for the option and aborts the command if the system is in maintenance 
mode and the option is not set.

##### `dump()` and `dumpVerbose()`
Better `var_dump` through [`VarDumper`](http://symfony.com/doc/3.4/components/var_dumper/introduction.html). 

## Example
```php
<?php

namespace AppBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Dumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AwesomeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('awesome:command')
            ->setDescription('Awesome command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // dump
        $this->dump("Isn't that awesome?");

        // add newlines through flags
        $this->dump("Dump #2", Dumper::NEWLINE_BEFORE | Dumper::NEWLINE_AFTER);

        // only dump in verbose mode
        $this->dumpVerbose("Dump verbose", Dumper::NEWLINE_BEFORE);
        
        // Output as white text on red background.
        $this->writeError('oh noes!');
    }
}
```

### Usage
Call `bin/console list` script from the command line to get a list of available commands. To call 
a command, use `bin/console <subcommand>`.

> Be sure to run the console with the PHP user to prevent writing permissions issues later, either by calling `php bin/console` or by switching to the appropriate user, for instance on Debian system `su -l www-data -s /bin/bash`.

##### Examples:
```php 
# get a list of all registered commands
$ ./bin/console list
 
# call the foo:bar command
$ ./bin/console foo:bar
```
