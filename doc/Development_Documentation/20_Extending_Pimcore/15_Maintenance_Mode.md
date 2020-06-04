# Maintenance Mode

Pimcore offers a maintenance mode, which restricts access to the admin user interface to the user that enabled the maintenance mode. It is session based 
and no other user will be able to access the website or the admin interface. 

All other users get a [default "Temporary not available" page](https://rawgit.com/pimcore/pimcore/master/bundles/CoreBundle/Resources/misc/maintenance.html)
displayed. 

Moreover, maintenance scripts and headless executions of Pimcore will be prevented.  
The Maintenance Mode is also activated by Pimcore during Pimcore Update.

In addition, you can enable or disable the maintenance mode via the following console command:

```shell script
bin/console pimcore:maintenance-mode --enable
bin/console pimcore:maintenance-mode --disable
``` 

## Customize Maintenance Page

Overwrite the service `Pimcore\Bundle\CoreBundle\EventListener\MaintenancePageListener` in your `app/config/services.yml`. 

```yaml
Pimcore\Bundle\CoreBundle\EventListener\MaintenancePageListener:
    calls:
        - [loadTemplateFromResource, ['@@AppBundle/Resources/misc/maintenance.html']]
    tags:
        - { name: kernel.event_listener, event: kernel.request, method: onKernelRequest, priority: 620 }
```
