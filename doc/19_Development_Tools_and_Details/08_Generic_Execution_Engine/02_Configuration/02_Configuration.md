# Configuration of the Generic Execution Engine

The Generic Execution Engine is configured via Symfony configuration files. Default configuration looks like this:

```yaml
pimcore_generic_execution_engine:
    error_handling: 'continue_on_error'
    execution_context:
        default:
            translations_domain: admin
```

## Define the error handling behavior
There are currently two error handling strategies available:

- `continue_on_error`: The execution of the job continues even if a step execution fails.
- `stop_on_first_error`: The execution of the job stops if a step execution fails.

You can define the global (used for all job runs as fallback) error handling strategy in the configuration file:
```yaml
pimcore_generic_execution_engine:
    error_handling: 'stop_on_first_error'
```

## Define the execution context
Sometimes, it's necessary to customize your translation domain or error handling strategy, depending on your bundle or specific job run.

You can achieve this by defining a custom execution context:
```yaml
pimcore_generic_execution_engine:
    execution_context:
        my_custom_context:
            translations_domain: my_custom_domain
            error_handling: 'continue_on_error'
```

You can then pass the name of your execution context when defining a new job run:
```php

$jobExecutionAgent->startJobExecution($job, $owner, 'my_custom_context');

```
Based on the execution context, the translation domain and error handling strategy are set for the job run. 
By default, the `admin` translation domain is used. For error handling, the global configuration strategy, which is `continue_on_error`, is used as a fallback.
Currently, only the translation domain and error handling strategy can be customized per execution context.