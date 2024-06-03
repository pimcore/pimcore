# Generic Execution Engine

Generic execution engine
- executes jobs asynchronously via [Symfony Messenger](https://symfony.com/doc/current/messenger.html).
- traces and logs state of job runs.
- manages (start, cancel, restart) job runs.

The execution is based on the [Symfony Messenger](https://symfony.com/doc/current/messenger.html#consuming-messages-running-the-worker) queue.
If activated, the selected jobs are automatically executed.

Messages are dispatched via `pimcore_generic_execution_engine` transport. Please ensure you have workers processing this transport.