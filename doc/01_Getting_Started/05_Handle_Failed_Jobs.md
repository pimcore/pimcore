# Handle Failed Jobs

If some maintenance jobs are failed in the processing, they are discarded from the respective transport after defined retries. 
However, you can move the failed jobs to a new transport (e.g. `pimcore_failed_jobs`) instead of discarding them completely with following config:
```yaml
framework:
    messenger:
        transports:
            pimcore_failed_jobs:
                dsn: "doctrine://default?queue_name=pimcore_failed_jobs&table_name=messenger_messages_pimcore_failed"

            pimcore_core:
                dsn: "doctrine://default?queue_name=pimcore_core"
                failure_transport: pimcore_failed_jobs
```
which can be re-processed later after fixing the underlying issue with command `bin/console messenger:consume pimcore_failed_jobs`.

Please follow the [Symfony docs](https://symfony.com/doc/current/messenger.html#saving-retrying-failed-messages) for options on failed jobs processing.