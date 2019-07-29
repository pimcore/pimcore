# Maintenance Tasks

Pimcore offers you to run scheduled maintenance tasks. This allows you to periodically do stuff like cleanups. 

## Register a new Maintenance Task

To register a new Maintenance Task, create a new class and implement the interface `Pimcore\Maintenance\TaskInterface`. Register your class to the symfony container with the tag `pimcore.maintenance.task` and a `type` attribute:   

```yaml
AppBundle\Maintenance\MyMaintenanceTask:
    tags:
        - { name: pimcore.maintenance.task, type: my_maintenance_task }
```

Pimcore will then call your maintenance task on the maintenance cron job you have to configure. You will have to take care about timing operations inside the Task yourself.
