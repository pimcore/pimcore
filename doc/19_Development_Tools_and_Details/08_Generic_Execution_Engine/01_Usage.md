# Working with the Generic Execution Engine

Working with the Generic Execution Engine consists of the several steps.

## Define Jobs
Jobs are defined and configured via `Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job` objects which contain
a name, steps (with defined message class and optional step specific configuration), selected elements and
environment data (e.g. content that is entered by a user and that should be available in job step handlers).

Create a job as follows

```php 
$job = new Job(
  'my-first-job',
  [
    new JobStep('Step 1', MyFirstTestMessage::class, '', []),
    new JobStep('Step 2', MySecondTestMessage::class, '', []),
  ],
  [new ElementDescriptor('object', 234)],
  [
     'foo' => 'bar'
  ]
);
```

## Executing Jobs and Managing Job Runs
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

## Job Runs
Job Runs are the entity for storing information about runs of a job. There is the
[`Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface`] for all kind of CRUD operations on job runs.

### Localization of Current Message
The current message of a job run can be localized. That means, that in user interface, the message is translated into
the users' language. To ease handling of these messages, the `JobRunRepository` provides a specialized
`updateLogLocalized` method, which helps you to create a localized log entry. It writes the untranslated message to the
job run object, but translates the message to english and adds it to the job run log, based on the execution context of the Job Run.

You can pass the `message` to the method, which then again can be translated using Pimcore's `Translations` menu by selecting `your custom` domain.
In addition, it is also possible to add a translation directly in the corresponding  `your-domain.<language>.yaml` file.

:::info
By default, the admin domain is used for the translation. If you want to use a different domain, you can set it in the `pimcore_generic_execution_engine` configuration.
:::

As example see:
```php 
$this->jobRunRepository->updateLogLocalized(
    $jobRun, 'pimcore_copilot_job_execution_job_cancelled', ['%job_run_id%' => $jobRun->getId()]
);
```

## Job Run Error Logs
Job Run Error Logs are the entity for storing log information about Job Run. There is the
[`Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface`] for all kind of CRUD operations on these logs.
