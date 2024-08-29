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

## Accessing JobStep in Handler
The `JobStep` object can be accessed in the handler via the `getJobStep` method. This can be useful if you need to access the step configuration in the handler.

```php
$jobRun = $this->getJobRun($message);
$steps = $jobRun->getJob()?->getSteps();
if($steps !== null) {
    $step = $steps[$jobRun->getCurrentStep()] ?? null;
    if($step) {
        return $step->getSelectionMode();
    }
}
```

## Cancel Job Run

To cancel a job run, you can use the `cancelJobRun` method of the `JobExecutionAgentInterface`. 
This method accepts the job run id as an argument.

```php
$jobExecutionAgent->cancelJobRun($jobRun->getId());
```

The state of the job run will be set to `cancelled` and the job run will be stopped.

## Rerun Job Run

To rerun a job run, you can use the `rerunJobRun` method of the `JobExecutionAgentInterface`.

```php
$jobExecutionAgent->rerunJobRun($jobRun->getId(), $ownerId);
```

The state of the job run will be set to `running` and the job run will be restarted.

## Cancel single steps of a Job Run

Right now it is not possible to cancel single steps of a job run. Currently, only the whole job run can be cancelled.

## Job Run States

The following states are available for a job run:

- `running` - The job run is currently running.
- `failed` - The job run has failed.
- `finished` - The job run has completed successfully.
- `cancelled` - The job run has been cancelled.
- `finished_with_errors` - The job run has completed, but one or more errors occurred.

## Adding additional log entries

To update the log you have to inject the `JobRunExtractorInterface` and use its `logMessageToJobRun` method.
This can be useful if you want to provide additional information about why a job run has was cancelled or failed.

```php
 $this->jobRunExtractor->logMessageToJobRun(
            $jobRun,   
            'translation_key',
            [
                '%param1%' => $var1,
                '%param2%' => $var2                
            ]
);
```

## Job Run Error Logs
Job Run Error Logs are the entity for storing log information about Job Run. There is the
[`Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface`] for all kind of CRUD operations on these logs.
