---
title: Configuration
description: Error handling strategies and execution context customization.
---

# Configuration of the Generic Execution Engine

Configure the Generic Execution Engine via Symfony configuration files. Default configuration:

```yaml
pimcore_generic_execution_engine:
    error_handling: 'continue_on_error'
    execution_context:
        default:
            translations_domain: admin
```

## Error Handling Behavior

Two error handling strategies are available:

- `continue_on_error` - Job execution continues even if a step fails.
- `stop_on_first_error` - Job execution stops on the first step failure.

Set the global error handling strategy (used as fallback for all job runs):
```yaml
pimcore_generic_execution_engine:
    error_handling: 'stop_on_first_error'
```

## Execution Context

Define custom execution contexts to override the translation domain
or error handling strategy per bundle or job run:

```yaml
pimcore_generic_execution_engine:
    execution_context:
        my_custom_context:
            translations_domain: my_custom_domain
            error_handling: 'continue_on_error'
```

Pass the execution context name when starting a job run:
```php
$jobExecutionAgent->startJobExecution($job, $owner, 'my_custom_context');
```

The execution context sets the translation domain and error handling strategy for that run.
By default, the `admin` translation domain is used.
For error handling, the global configuration (`continue_on_error`) serves as fallback.
Currently, only the translation domain and error handling strategy support
per-context customization.
