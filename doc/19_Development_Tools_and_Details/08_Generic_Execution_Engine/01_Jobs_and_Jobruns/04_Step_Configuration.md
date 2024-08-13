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

Additionally, you can refer environment variables in the configuration by using the following syntax: `job_env('<env_variable_name>')`.

## Selection Processing mode
The selection processing mode of a step is an enum that defines how the step should process the selected elements.

- `SelectionProcessingMode::FOR_EACH`: The step is executed for each selected element.
For example if you pass 10 selected elements to the job, the message gets dispatched 10 times and the step handler therefore is executed 10 times.
Use `getSubjectFromMessage()` in `AbstractAutomationActionHandler` method to access the current element in the handler.

- `SelectionProcessingMode::FOR_ALL`: The step is executed once for all selected elements.
If you pass 10 selected elements to the job, the message gets dispatched once and the step handler therefore is executed once.
Use `getSubjectsFromMessage()` in `AbstractAutomationActionHandler` method to access all elements in the handler.