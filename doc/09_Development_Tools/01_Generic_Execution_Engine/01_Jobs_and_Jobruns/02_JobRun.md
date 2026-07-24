---
title: Job Runs
description: Manage job run state, logging, cancellation, and rerun.
---

# Job Runs

Job runs store information about each execution of a job.
Use [`Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface`]
for all CRUD operations on job runs.

## Localization of Current Message

The current message of a job run supports localization. In the user interface,
the message is translated into the user's language.
The `JobRunRepository` provides a `updateLogLocalized` method that writes
the untranslated message to the job run object and adds an English translation
to the job run log, based on the execution context.

Pass the `message` parameter to the method. Translate it using Pimcore's
`Translations` menu by selecting your custom domain,
or add a translation directly in the corresponding `your-domain.<language>.yaml` file.

:::info
By default, the `admin` domain handles translations. To use a different domain,
set it in the `pimcore_generic_execution_engine` configuration.
:::

Example:
```php
$this->jobRunRepository->updateLogLocalized(
    $jobRun, 'pimcore_copilot_job_execution_job_cancelled', ['%job_run_id%' => $jobRun->getId()]
);
```

## Accessing JobStep in Handler

Access the `JobStep` object in a handler via the job run's step list.
This is useful for reading step configuration at runtime.

```php
$jobRun = $this->getJobRun($message);
$steps = $jobRun->getJob()?->getSteps();
if($steps !== null) {
    $step = $steps[$jobRun->getCurrentStep()] ?? null;
    if($step) {
        return $step->getSelectionProcessingMode();
    }
}
```

## Cancel Job Run

Cancel a job run via `JobExecutionAgentInterface::cancelJobRun()`:

```php
$jobExecutionAgent->cancelJobRun($jobRun->getId());
```

The state changes to `cancelled` and execution stops.

## Rerun Job Run

Rerun a job run via `JobExecutionAgentInterface::rerunJobRun()`:

```php
$jobExecutionAgent->rerunJobRun($jobRun->getId(), $ownerId);
```

The state resets to `running` and execution restarts.

## Cancel Single Steps

Cancelling individual steps is not supported.
Cancel only the entire job run.

## Job Run States

| State | Description |
|-------|-------------|
| `running` | Currently executing |
| `failed` | Execution failed |
| `finished` | Completed successfully |
| `cancelled` | Cancelled by user |
| `finished_with_errors` | Completed with one or more errors |

## Adding Additional Log Entries

Inject `JobRunExtractorInterface` and use `logMessageToJobRun` to add
custom log entries, for example to explain why a job run failed or was cancelled:

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

Job run error logs store detailed log information per job run.
Use [`Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface`]
for CRUD operations on these logs.
