# CLI and Pimcore Console

Pimcore can be executed headless and has a very powerful PHP API. As a consequence of these two aspects, 
it is possible to do automate pretty every thing within Pimcore. 

There are two ways to getting up and running - simple CLI scripts and Pimcore Console. 

## Simple CLI Scripts
It is pretty simple to create CLI scripts that can interact with Pimcore. 
Just include `pimcore/cli/startup.php`. So Pimcore gets started headless and you can use the whole 
Pimcore API in your script. 

The following script is a very basic cli script, which imports Pimcore objects.
Although this method is working and very simple, we recommend using `Pimcore\Console` instead. 

It can be executed from the directory where it is located like this: `php /website/cli/import.php -f`

Source Code for `import.php`:

```php
<?php
 
include("../../pimcore/cli/startup.php");
 
use \Pimcore\Model\Object;
// create some random objects ;-)
for ($i = 0; $i < 60; $i++) {
    $o = new Object\News();
    $o->setKey(uniqid() . "-" . $i);
    $o->setParentId(1);
    $o->setPublished(true);
    $o->save();
    echo("Created object " . $o->getFullPath() . "\n");
}
```


## Pimcore Console

Pimcore integrates the `Symfony\Console` component and provides `pimcore/cli/console.php` as single 
entry point to console commands registered to the `Symfony\Console` application. You can add custom
commands either by hooking into an event or by placing your commands in predefined locations.

### Usage
Call the `console.php` script from the command line to get a list of available commands. To call 
a command, use `console.php <subcommand>`. 

##### Examples:
```php 
# get a list of all registered commands
$ php pimcore/cli/console.php
 
# or
$ php pimcore/cli/console.php list
 
# call the foo:bar command
$ php pimcore/cli/console.php foo:bar
```


### Implementing own Commands
Have a look at the `Symfony\Console` [documentation](http://symfony.com/doc/current/components/console/introduction.html) 
for details how commands are implemented. However, it makes sense to let your command classes extend 
`Pimcore\Console\AbstractCommand` to get some defaults like the `--ignore-maintenance-mode` option 
and a helper for the [Symfony VarDumper Component](http://symfony.com/doc/current/components/var_dumper/index.html) 
set up automatically (see below).

### Registering Commands
There are currently 2 methods to add commands. Either you put your commands into a predefined location 
where commands are autoloaded from or you hook into the initialization process and add your commands 
manually.

#### Autoloaded Commands
The console application tries to autoload commands from a list of given namespaces. Currently the 
following namespaces are taken into consideration (more are likely to follow):

| Namespace | Directory |
| --------- | --------- |
| `Pimcore\Console\Command` | `/pimcore/lib/Pimcore/Console/Command` |
| `Website\Console\Command` | `/website/lib/Website/Console/Command` |

To have your command autoloaded, it must match a couple of prerequisites:

* It must be placed in one of the namespaces listed above 
(e.g. `Website\Console\Command\AwesomeCommand` in `/website/lib/Website/Console/Command/AwesomeCommand.php`)
* The class name must end in Command, e.g. `AwesomeCommand`
* The class must inherit `Symfony\Component\Console\Command\Command`, ideally you achieve this by 
extending `Pimcore\Console\AbstractCommand`


#### Manually registered commands
Upon initialization the console application emits the `system.console.init` event, which you can use 
to hook into the initialization process and to add your commands. Again, there are 2 ways to do this:

##### Using the `ConsoleCommandPluginTrait` to add a list of commands
* Create a plugin and let your plugin class extend `Pimcore\Console\AbstractConsoleCommandPlugin` 
 (or use the `Pimcore\Console\ConsoleCommandPluginTrait` and call the `initConsoleCommands()` method 
 yourself in `init()`).
* Implement the `getConsoleCommands()` method returning an array of commands to register.

##### Handle the system.console.init event manually to have more control
See the trait mentioned above for an example on how to handle the event. You'll get the 
`Pimcore\Console\Application` object passed as event target and can use its API to add commands. 

An example:
```php
<?php
\Pimcore::getEventManager()->attach('system.console.init', function(\Zend_EventManager_Event $e) {
    /** @var \Pimcore\Console\Application $application */
    $application = $e->getTarget();
 
    // add a namespace to autoload commands from
    $application->addAutoloadNamespace('ConsoleDemoPlugin\\Console', PIMCORE_DOCUMENT_ROOT . '/plugins/ConsoleDemoPlugin/lib/ConsoleDemoPlugin/Console');
 
    // add a single command
    $application->add(new My\Custom\Namespace\AwesomeCommand());
});
```

### Helpers provided by `Pimcore\Console\AbstractConsoleCommand`
The `AbstractConsoleCommand` base class provides helpers which make your life easier.

##### `--ignore-maintenance-mode`
The console application implicitly adds the `--ignore-maintenance-mode` option found in other scripts.
`AbstractConsoleCommand` checks for the option and aborts the command if the system is in maintenance 
mode and the option is not set.

##### `dump()` and `dumpVerbose()`
Better `var_dump` through [`VarDumper`](http://symfony.com/doc/current/components/var_dumper/introduction.html). 

Usage:
```php
<?php
 
namespace Pimcore\Console\Command;
 
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Dumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
 
class TestCoreCommand extends AbstractCommand
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
        $this->dump($this);
 
        // add newlines through flags
        $this->dump($this, Dumper::NEWLINE_BEFORE | Dumper::NEWLINE_AFTER);
 
        // only dump in verbose mode
        $this->dumpVerbose($this, Dumper::NEWLINE_BEFORE);
    }
}
```

##### writeError()
Shortcut to write an error. 

Usage:
```php
<?php
$this->writeError('oh noes!');
```

The call above will output `ERROR: oh noes!` as white text on red background.
