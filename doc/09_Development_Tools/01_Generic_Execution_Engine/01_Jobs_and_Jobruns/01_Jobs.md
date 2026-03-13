## Define Jobs
Jobs are defined and configured via `Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job` objects which contain
a name, steps (with defined message class and optional step specific configuration), selected elements and
environment data (e.g. content that is entered by a user and that should be available in job step handlers).

Create a job as follows

```php 
$job = new Job(
  'my-first-job',
  [
    new JobStep('Step 1', MyFirstTestMessage::class, '', [], StepSelectionMode::FOR_EACH),
    new JobStep('Step 2', MySecondTestMessage::class, '', [], StepSelectionMode::FOR_EACH),
  ],
  [new ElementDescriptor('object', 234)],
  [
     'foo' => 'bar'
  ]
);
```

## Executing Jobs
Execution of jobs is done via [`Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface`] service,
which provides all sorts of methods for executing and managing running jobs.

To execute a job use the `startJobExecution` method which accepts 3 arguments:
1. The job object
2. Owner ID (optional) - the ID of the user who owns the job run
3. Execution context (optional) - the context in which the job should be executed. This is used for example to get the translation domain (by default admin) for localized messages.

```php
$jobExecutionAgent->startJobExecution($job, null, 'my-custom-context');
```

:::caution
Be aware that the execution engine does not retry failed messages.

```yaml
pimcore_generic_execution_engine:
    dsn: 'doctrine://default?queue_name=pimcore_generic_execution_engine'
    retry_strategy:
        max_retries: 0 # no retries to prevent data corruption
```
:::

After starting the job execution, the job run is created and the job steps are executed based on the configuration.
For further information on how to handle job runs (cancel, stop, ...), see [Job Runs](./02_JobRun.md).