---
title: Jobs and Job Runs
description: Define jobs with steps and elements, manage job run lifecycle.
---

# Jobs and Job Runs

The Generic Execution Engine processes work as **jobs** containing one or more **steps**.
Each step dispatches a Symfony Messenger message to a handler.
A **job run** tracks execution state, logging, and errors for a single invocation of a job.

- [Jobs](./01_Jobs.md) - Define jobs with steps, elements, and environment data;
  execute them via `JobExecutionAgentInterface`.
- [Job Runs](./02_JobRun.md) - Inspect run state, access step configuration in handlers,
  cancel or rerun jobs, and add log entries.
- [Step Configuration](./04_Step_Configuration.md) - Configure step properties including
  message class, custom configuration, and selection processing mode.
