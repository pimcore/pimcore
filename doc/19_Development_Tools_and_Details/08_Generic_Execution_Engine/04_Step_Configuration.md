# Step configuration

The configuration of a step is done via the `JobStep` object. 
The `JobStep` is part of the `Job` object and contains the following properties:

- `name`: The name of the step.
- `messageClass`: The message class that should be dispatched.
- `configuration`: The configuration of the step.
- `selectionMode`: The selection mode of the step.

## Name
The name of the step is a string that is used to identify the step in the job run log.

## Message class
The message class is the class of the message that should be dispatched when the step is executed.

## Configuration
The configuration of a step is an array that can contain any kind of data that is necessary for the step handler to execute the step.

## Selection mode
The selection mode of a step is an enum that defines how the step should be executed.

- `StepSelectionMode::FOR_EACH`: The step is executed for each selected element.
For example if you pass 10 selected elements to the job, the message gets dispatched 10 times and the step handler therefore is executed 10 times.
- `StepSelectionMode::FOR_ALL`: The step is executed once for all selected elements.
If you pass 10 selected elements to the job, the message gets dispatched once and the step handler therefore is executed once.

