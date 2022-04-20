# Maintenance Tasks

Pimcore offers you to run scheduled maintenance tasks. This allows you to periodically do stuff like cleanups. 

## Register a new Maintenance Task

To register a new Maintenance Task, create a new class and implement the interface `Pimcore\Maintenance\TaskInterface`. 
e.g. App\Maintenance\MyMaintenanceTask.php
Feel free to use samples from /vendor/pimcore/pimcore/lib/Maintenance/Tasks

Register your class to the symfony container with the tag `pimcore.maintenance.task` and a `type` attribute:   
e.g. in file App/Resources/config/maintenance.yaml
```yaml
App\Maintenance\MyMaintenanceTask:
    tags:
        - { name: pimcore.maintenance.task, type: my_maintenance_task }
```
Feel free to use samples from /vendor/pimcore/pimcore/bundles/CoreBundle/Resources/config/maintenance.yaml how to inject arguments

Pimcore will then call your maintenance task on the maintenance cron job you have to configure. e.g.
```sh
crontab -e
*/10 * * * * /path/to/command # which command to run for pimcore maintenance tasks?
```
See more https://pimcore.com/docs/customer-management-framework/current/Cronjobs.html 

You will have to take care about timing operations inside the Task yourself.
Sample from /vendor/pimcore/pimcore/lib/Maintenance/Tasks/LowQualityImagePreviewTask.php

```php
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, LockFactory $lockFactory)
    {
        $this->logger = $logger;
        $this->lock = $lockFactory->createLock(self::class, 86400 * 2); // lock the task for 24 hours so it is executed once a day
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (date('H') <= 4 && $this->lock->acquire()) {
            // execution should be only sometime between 0:00 and 4:59 -> less load expected
            $this->logger->debug('Execute some code');
```
