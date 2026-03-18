---
title: Generic Execution Engine
description: Asynchronous job execution via Symfony Messenger with state tracking and logging.
---

# Generic Execution Engine

## Overview

The Generic Execution Engine provides:
* Asynchronous job execution via Symfony Messenger
* State tracking and logging for job runs
* Job run management (start, cancel, restart)

## Installation

1. Install the required dependencies:
   ```bash
   composer require phpdocumentor/reflection-docblock symfony/property-info
   ```

2. Enable the `PimcoreGenericExecutionEngineBundle` in `/config/bundles.php`:
   ```php
   Pimcore\Bundle\GenericExecutionEngineBundle\PimcoreGenericExecutionEngineBundle::class => ['all' => true],
   ```

3. Install the bundle:
   ```bash
   bin/console pimcore:bundle:install PimcoreGenericExecutionEngineBundle
   ```

## Configuration

### 1. Message Consumption

Ensure at least one Symfony Messenger worker consumes the `pimcore_generic_execution_engine` transport.

#### Docker Deployments (Recommended for Production)

Pimcore recommends using supervisord for production deployments. Add this configuration to your `supervisord.conf`:

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

This ensures the message consumer runs automatically and restarts on failure.

#### Alternative: Cron Job (Simple Deployments)

Add this to your crontab to run the consumer every minute:
```bash
* * * * * cd /path/to/your/pimcore/project && php bin/console messenger:consume pimcore_generic_execution_engine --time-limit=60 > /dev/null 2>&1
```

#### Development: Manual Execution

Run the consumer manually during development:
```bash
php bin/console messenger:consume pimcore_generic_execution_engine
```


### 2. Messenger Transport

This step is optional unless you use a different transport such as RabbitMQ.

**Docker example setup:**
Add this transport to `.docker/messenger.yaml` to maintain consistency with your existing RabbitMQ configuration:

```yaml title="messenger.yaml"
framework:
    messenger:
        transports:
            # ... your existing transports
            pimcore_generic_execution_engine: 'amqp://rabbitmq:5672/%2f/pimcore_generic_execution_engine'
```

**Custom setup:**
The default Doctrine transport requires no additional configuration. Override it in `config/packages/messenger.yaml` to use a different transport.

## Verification

To verify the setup:

1. Check that your message consumer is running:
   ```bash
   php bin/console messenger:stats
   ```

2. Monitor the logs for transport-related errors.

3. Test by creating and running a job through the execution engine.
