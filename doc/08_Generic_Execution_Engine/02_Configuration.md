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

You can define the error handling strategy in the configuration file:

```yaml
pimcore_generic_execution_engine:
    error_handling: 'stop_on_first_error'
```

## Define the execution context
Execution context enables users to overwrite the default execution engine configuration. Currently, only the translations domain can be overwritten.

```yaml
pimcore_generic_execution_engine:
    execution_context:
        my_custom_context:
            translations_domain: my_custom_domain
```