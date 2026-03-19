---
title: Maintenance Tasks
description: Register scheduled maintenance tasks with the Pimcore maintenance system.
---

# Maintenance Tasks

Pimcore runs scheduled maintenance tasks for periodic operations such as
cleanups, cache invalidation, or data synchronization. The maintenance cron job
must be configured correctly - see the
[Installation Guide](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/README.md).

## Register a Maintenance Task

Create a class that implements `Pimcore\Maintenance\TaskInterface` and register
it as a Symfony service with the `pimcore.maintenance.task` tag and a `type`
attribute.

The interface requires a single `execute(): void` method:

```php
<?php

namespace App\Maintenance;

use Pimcore\Maintenance\TaskInterface;

class MyMaintenanceTask implements TaskInterface
{
    public function execute(): void
    {
        // Perform cleanup, sync, or other periodic work.
        // This runs on every maintenance cycle, so guard
        // expensive operations with your own timing logic
        // (e.g. check a timestamp or lock file and skip
        // if the last run was too recent).
    }
}
```

Register the service:

```yaml
App\Maintenance\MyMaintenanceTask:
    tags:
        - { name: pimcore.maintenance.task, type: my_maintenance_task }
```

Pimcore calls `execute()` on every maintenance cron run. The framework does not
throttle individual tasks, so your implementation must handle frequency checks
internally (for example, by storing a last-run timestamp and returning early
when the interval has not elapsed).

## Use a Separate Messenger Transport

To offload the task to an asynchronous messenger transport, add the
`messengerMessageClass` attribute to the tag:

```yaml
App\Maintenance\MyMaintenanceTask:
    tags:
        -
            name: pimcore.maintenance.task
            type: my_maintenance_task
            messengerMessageClass: '\App\Messenger\MyMaintenanceMessage'
```

:::note
You must implement both the message class and its handler, then route
the message to the desired transport in your messenger configuration.
:::

See `Pimcore\Messenger\ScheduledTaskMessage` and its handler
`Pimcore\Messenger\Handler\ScheduledTaskHandler` for a full working example.
