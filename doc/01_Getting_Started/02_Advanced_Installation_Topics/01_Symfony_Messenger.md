# Symfony Messenger

## Handle Failed Jobs

If maintenance jobs fail during processing, they are discarded from the respective transport after a defined number of retries. 
However, you can move the failed jobs to a new transport (e.g. `pimcore_failed_jobs`) instead of discarding them completely with following config:
```yaml
framework:
    messenger:
        transports:
            pimcore_failed_jobs:
                dsn: "doctrine://default?queue_name=pimcore_failed_jobs&table_name=messenger_messages_pimcore_failed"

            pimcore_core:
                dsn: "doctrine://default?queue_name=pimcore_core"
                # For RabbitMQ (recommended) use this as example:
                # dsn: "amqp://rabbitmq:5672/%2f/pimcore_core"
                failure_transport: pimcore_failed_jobs
```
which can be re-processed later after fixing the underlying issue with command `bin/console messenger:consume pimcore_failed_jobs`.

Please follow the [Symfony docs](https://symfony.com/doc/current/messenger.html#saving-retrying-failed-messages) for options on failed jobs processing.

We recommend [RabbitMQ](https://www.rabbitmq.com/#getstarted) as a message queue. For a tutorial, check this [link](https://www.rabbitmq.com/tutorials/tutorial-one-php.html). For an example configuration, refer to [this link](https://github.com/pimcore/skeleton/blob/11.x/.docker/messenger.yaml).
