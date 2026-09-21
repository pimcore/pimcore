---
title: Jobs
description: Define and execute jobs with the JobExecutionAgentInterface.
---

# Define Jobs

Configure jobs via `Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job` objects.
A job contains a name, steps (each with a message class and optional step-specific configuration),
selected elements, and environment data (e.g. user input available to step handlers).

```php
$job = new Job(
  'my-first-job',
  [
    new JobStep('Step 1', MyFirstTestMessage::class, '', [], SelectionProcessingMode::FOR_EACH),
    new JobStep('Step 2', MySecondTestMessage::class, '', [], SelectionProcessingMode::FOR_EACH),
  ],
  [new ElementDescriptor('object', 234)],
  [
     'foo' => 'bar'
  ]
);
```

# Execute Jobs

Execute jobs via the
[`Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface`] service.

The `startJobExecution` method accepts three arguments:
1. The job object
2. Owner ID (optional) - the ID of the user who owns the job run
3. Execution context (optional) - determines the translation domain
   (default: `admin`) for localized messages

```php
$jobExecutionAgent->startJobExecution($job, null, 'my-custom-context');
```

:::caution
The execution engine does not retry failed messages.

```yaml
pimcore_generic_execution_engine:
    dsn: 'doctrine://default?queue_name=pimcore_generic_execution_engine'
    retry_strategy:
        max_retries: 0 # no retries to prevent data corruption
```
:::

After starting execution, a job run is created and steps execute
based on the configuration.
For run management (cancel, rerun, logging), see [Job Runs](./02_JobRun.md).
