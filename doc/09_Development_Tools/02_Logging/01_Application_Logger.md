---
title: Application Logger
description: Log application events with database storage and email notifications.
---

# Application Logger

:::caution

To use this feature, enable the `PimcoreApplicationLoggerBundle` in your `bundle.php` file
and install it:

`bin/console pimcore:bundle:install PimcoreApplicationLoggerBundle`

:::

## General

The Application Logger bundle lets developers log events and errors
within a Pimcore application.

<div class="inline-imgs">

View and search logs in Pimcore Studio under `System` -> `Application Logger`:

</div>

![Application logger preview](../../img/applogger_backend_preview.png)

## Configuration

The Application Logger supports minimum, maximum, or fixed log levels.

Log all messages with level `debug` or `info`:
```yaml
applicationlog:
    loggers:
        db:
            min_level_or_list: ['debug', 'info']
```

Log all messages from level `info` through `emergency`:
```yaml
applicationlog:
    loggers:
        db:
            min_level_or_list: 'info'
            max_level: 'emergency'
```

## How to Create Log Entries

The Application Logger is a PSR-3 compatible component available as service
`Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogger`.

### Basic Usage - Example

#### Controller / Action

```php
<?php

namespace App\Controller;

use Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogger;
use Pimcore\Controller\FrontendController;

class TestController extends FrontendController
{
    // injected as action argument (controller needs to be registered as service)
    public function testAction(ApplicationLogger $logger): void
    {
        $logger->error('Your error message');
        $logger->alert('Your alert');
        $logger->debug('Your debug message', ['foo' => 'bar']); // additional context information
    }

    public function anotherAction(): void
    {
        // fetched from container
        $logger = $this->get(ApplicationLogger::class);
        $logger->error('Your error message');
    }
}
```

#### Dependency Injection

```yaml
App\YourService:
    calls:
        - [setLogger, ['@Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogger']]
```

Or use autowiring:

```yaml
services:
    _defaults:
        autowire: true

    App\YourService: ~
```

```php
<?php

namespace App;

use Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogger;

class YourService
{
    /**
     * @var ApplicationLogger
     */
    private $logger;

    public function __construct(ApplicationLogger $logger)
    {
        $this->logger = $logger;

        $logger->debug('Hello from YourService');
    }
}
```

### Usage as Monolog Handler

Instead of using the `ApplicationLogger` class directly, configure Monolog
to use the Application Logger as a Monolog handler.
Pimcore provides the `ApplicationLoggerDb` handler, preconfigured as a service:

```yaml
monolog:
    handlers:
        # monolog allows us to register custom handlers via type: service
        # note that the only supported extra option besides type and id is channels
        application_logger_db:
            type: service
            id: Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb
            channels: ["application_logger"]
```

The channel(s) must exist. Create them by [configuring them manually](https://symfony.com/doc/current/logging/channels_handlers.html#creating-your-own-channel)
or by using [DI tags](https://symfony.com/doc/current/reference/dic_tags.html#dic-tags-monolog)
to select the logger for the target channel.
When using DI tags, Monolog creates the channel implicitly.

> **IMPORTANT**: The `ApplicationLoggerDb` handler depends on the database connection.
  Exclude channels logging database queries (typically the `doctrine` channel)
  to avoid infinite loops.
  Either specify an allowlist of supported channels (as shown above)
  or exclude the `doctrine` channel by setting channels to `["!doctrine"]`.

As the `type: service` handler config does not support filtering by log level,
use the `filter` handler type to wrap the Application Logger:

```yaml
monolog:
    handlers:
        # The filter handler can be used to filter for a given log level.
        # Note that the supported channels are now configured on the filter
        # handler. To filter by level you can set accepted_levels or min_level and max_level.
        # See https://github.com/symfony/monolog-bundle/blob/master/DependencyInjection/Configuration.php#L97
        # for details.
        application_logger_filter:
            type: filter
            channels: ["application_logger"]
            handler: application_logger_db
            min_level: ERROR
        application_logger_db:
            type: service
            id: Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb
```

Combine this handler with other log handlers such as the
[Fingers Crossed Handler](https://symfony.com/doc/current/logging.html#handlers-that-modify-log-entries).
See the [Symfony Logging documentation](https://symfony.com/doc/current/logging.html) for details.

Once the handler is configured, use it like any other Monolog logger by specifying
a DI tag for the target channel:

```php
<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;

// we take a controller as example here, but this can be any service
// no need to extend a base controller here as we inject our dependencies
// via DI
class TestController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function testAction(): void
    {
        $this->logger->error('Your error message');
    }
}
```

The service definition adds a DI tag to specify which logger to inject:

```yaml
services:
    App\Controller\TestController:
        arguments:
            $logger: '@logger'
        tags:
            - { name: monolog.logger, channel: application_logger }
```

Autowire the logger channel by naming the argument as `(channel name in camel case) + Logger`.

Example for channel `foo_bar`:

```php
  public function __construct(LoggerInterface $fooBarLogger)
  {
      $this->logger = $fooBarLogger;
  }
```

More details on [Logging Channel Handlers](https://symfony.com/doc/current/logging/channels_handlers.html#how-to-autowire-logger-channels).

### Special Context Variables

Three context variables have special functionality: `fileObject`, `relatedObject`, `component`.

```php
<?php

namespace App\Controller;

use Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogger;
use Pimcore\Bundle\ApplicationLoggerBundle\FileObject;
use Pimcore\Model\DataObject\AbstractObject;
use Symfony\Component\HttpFoundation\Response;

class TestController
{
    public function testAction(ApplicationLogger $logger): Response
    {
        $fileObject = new FileObject('some interesting data');
        $myObject   = DataObject::getById(73);

        $logger->error('my error message', [
            'fileObject'    => $fileObject,
            'relatedObject' => $myObject,
            'component'     => 'different component',
            'source'        => 'Stack trace or context-relevant information' // optional, if empty, gets automatically filled with class:method:line from where the log got executed
        ]);

        // ...
    }
}
```

In the Application Logger grid, the new row appears as *my error message* with a related object.

Click the row to navigate to the object editor via the *Related object* edit icon in the popup.

![App logger popup](../../img/applogger_backend_popup.png)


### Logging Exceptions

The Application Logger provides a helper method to log exceptions
and implicitly create a `FileObject` from the exception:

```php
<?php

use Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogger;

$exception = new \RuntimeException('failed :(');

// 1) When directly using the application logger (see basic usage above). Given your
//    logger is an instance of `ApplicationLogger`:

/** @var ApplicationLogger $appLogger */
$appLogger->logException('Oh no!', $exception, 'alert', $relatedObject, $component);

// 2) When using as monolog handler (see above). Given your logger is any PSR-3 compatible logger, you
//    can use a static helper to generate a log entry with the same file object as the logging call
//    above.

/** @var \Psr\Log\LoggerInterface $logger */
ApplicationLogger::logExceptionObject($logger, 'Oh no!', $exception, 'alert', $relatedObject);
```


### Setting an Individual Logger Level

Add a console logger and set the minimum logging level to *INFO*
(overwrites the log level in Pimcore configuration):

```php
$logger = \Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogger::getInstance("SAP_exporter", true);
// returns a PSR-3 compatible logger, registers a custom app logger as `pimcore.app_logger.SAP_exporter` on the service container
$logger->addWriter(new \Monolog\Handler\StreamHandler('php://output', \Monolog\Level::Info));
```

## Configuration

Configure the Application Logger in Pimcore Studio under the application logger settings:

![Application logger settings](../../img/applogger_settings.png)

When *Send log summary per mail* is activated, the defined receivers receive log entries by mail.
The priority controls which log messages the mail contains
(e.g. errors take precedence over info entries).

The archive function automatically creates new database tables (`application_logs_archive_*`)
for log entry archival.
In the example above, log entries move to archive tables after 30 days.
Optionally define a different database name for the archive tables.
