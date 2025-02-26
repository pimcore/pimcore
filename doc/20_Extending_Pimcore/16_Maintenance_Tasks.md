# Maintenance Tasks

Pimcore offers you to run scheduled maintenance tasks. This allows you to periodically do stuff like cleanups. 
It is essential that the maintenance cron job is set up perperly, see: [install guide](../01_Getting_Started/00_Installation/01_Webserver_Installation.md#5-maintenance-cron-job).  

## Register a new Maintenance Task

To register a new Maintenance Task, create a new class and implement the interface `Pimcore\Maintenance\TaskInterface`. Register your class to the symfony container with the tag `pimcore.maintenance.task` and a `type` attribute:   

```yaml
App\Maintenance\MyMaintenanceTask:
    tags:
        - { name: pimcore.maintenance.task, type: my_maintenance_task }
```

Pimcore will then call your maintenance task on the maintenance cron job you have to configure. You will have to take care about timing operations inside the Task yourself.

## Register a new Maintenance Task using a separate messenger transport

First follow the steps mentioned above, then add the `messengerMessageClass` property to your tag, as shown in the example below.
Pimcore then will make sure to add this specific message to the messenger bus.

```yaml
App\Maintenance\MyMaintenanceTask:
    tags:
        - { name: pimcore.maintenance.task, type: my_maintenance_task, messengerMessageClass: '\App\Messenger\MyMaintenanceMessage' }
```

**Please be aware:** 
You need to implemented both, the message and the handler class. 
After that you can route your message to the corresponding transport.

For a full example you can have a look at the `\Pimcore\Messenger\ScheduledTaskMessage` class and its usage.
