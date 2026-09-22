---
title: Step Configuration
description: Configure job step properties and selection processing modes.
---

# Step Configuration

Configure each step via the `JobStep` object.
A `JobStep` is part of a `Job` and contains the following properties:

- `name` - Identifies the step in the job run log.
- `messageClass` - The message class dispatched when the step executes.
- `configuration` - An array of arbitrary data available to the step handler.
- `selectionProcessingMode` - The selection processing mode (see below).

## Name

A string identifier for the step, displayed in the job run log.

## Message Class

The fully qualified class name of the message dispatched when the step executes.

## Configuration

An array containing any data the step handler needs.

Reference environment variables in the configuration using: `job_env('<env_variable_name>')`.

## Selection Processing Mode

An enum (`SelectionProcessingMode`) that defines how the step processes selected elements:

- **`SelectionProcessingMode::FOR_EACH`** - The step executes once per selected element.
  Passing 10 elements dispatches the message 10 times.
  Use `getSubjectFromMessage()` in `AbstractAutomationActionHandler` to access the current element.

- **`SelectionProcessingMode::ONCE`** - The step executes once regardless of how many elements
  are selected. Passing 10 elements dispatches the message once.
  Use `getSubjectsFromMessage()` in `AbstractAutomationActionHandler` to access all elements.
