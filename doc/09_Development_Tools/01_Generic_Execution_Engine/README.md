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

The Generic Execution Engine ships with Pimcore core and is installed as part of
[Pimcore Studio setup](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/03_Advanced_Installation_Topics/02_Pimcore_Studio_Setup.md).
The Studio setup guide also covers Symfony Messenger worker configuration
(supervisord, cron, development) and transport setup (Doctrine, RabbitMQ).

## Configuration

### Message Consumption

At least one Symfony Messenger worker must consume the `pimcore_generic_execution_engine` transport.
See the
[Pimcore Studio setup guide](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/03_Advanced_Installation_Topics/02_Pimcore_Studio_Setup.md)
for supervisord and cron configuration examples.

For development, run the consumer manually:
```bash
php bin/console messenger:consume pimcore_generic_execution_engine
```

### Messenger Transport

By default, the execution engine uses a Doctrine transport. No additional configuration is needed
unless you use a different transport such as RabbitMQ.
See [Symfony Messenger configuration](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/03_Advanced_Installation_Topics/01_Symfony_Messenger.md)
for transport setup details.

## Verification

To verify the setup:

1. Check that your message consumer is running:
   ```bash
   php bin/console messenger:stats
   ```

2. Monitor the logs for transport-related errors.

3. Test by creating and running a job through the execution engine.
