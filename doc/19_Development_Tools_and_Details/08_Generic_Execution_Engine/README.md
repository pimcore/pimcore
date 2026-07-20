# Generic Execution Engine

## Overview

The Generic Execution Engine provides:
* Asynchronous job execution via Symfony Messenger
* State tracing and logging for job runs  
* Job run management (start, cancel, restart)

## Installation

1. Update your `composer.json` to require the necessary dependencies:
   ```bash
   composer require phpdocumentor/reflection-docblock symfony/property-info
   ```

2. Enable the `PimcoreGenericExecutionEngineBundle` in your `/config/bundles.php` file by adding this line:
   ```php
   Pimcore\Bundle\GenericExecutionEngineBundle\PimcoreGenericExecutionEngineBundle::class => ['all' => true],
   ```

3. Install the bundle:
   ```bash
   bin/console pimcore:bundle:install PimcoreGenericExecutionEngineBundle
   ```

## Configuration

### 1. Message Consumption

Make sure at least one of your symfony messenger worker is consuming the `pimcore_generic_execution_engine` transport. 

#### Docker Deployments (Recommended for Production)

Pimcore recommends using supervisord for production deployments. If you're using the Pimcore Docker container, add this configuration to your `supervisord.conf` file:

```ini title="supervisord.conf"
[program:execution-engine]
command=php /var/www/html/bin/console messenger:consume pimcore_generic_execution_engine --memory-limit=250M --time-limit=3600
numprocs=1
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=/dev/fd/1
stdout_logfile_maxbytes=0
redirect_stderr=true
```

This ensures the message consumer runs automatically and restarts if it fails.

#### Alternative: Cron Job (Simple deployments)

Add this to your crontab to run the consumer every minute:
```bash
* * * * * cd /path/to/your/pimcore/project && php bin/console messenger:consume pimcore_generic_execution_engine --time-limit=60 > /dev/null 2>&1
```

#### Development: Manual Execution

Run the consumer manually for development:
```bash
php bin/console messenger:consume pimcore_generic_execution_engine
```


### 2. Messenger Transport

This step is optional unless you're using a different transport such as RabbitMQ in Pimcore docker-compose example.

**If using the Docker example setup:**
Add this transport to maintain consistency with your existing RabbitMQ configuration by updating `.docker/messenger.yaml` to match your other transport configurations.

```yaml title="messenger.yaml"
framework:
    messenger:
        transports:
            # ... your existing transports
            pimcore_generic_execution_engine: 'amqp://rabbitmq:5672/%2f/pimcore_generic_execution_engine'
```

**If using a custom setup:**
You can use the default Doctrine transport (no additional configuration needed) or override it in `config/packages/messenger.yaml` if you prefer a different transport.

## Verification

To verify your setup is working:

1. Check that your message consumer is running:
   ```bash
   php bin/console messenger:stats
   ```

2. Monitor the logs for any transport-related errors

3. Test by creating and running a simple job through the execution engine
