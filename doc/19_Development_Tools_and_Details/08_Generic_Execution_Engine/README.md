# Generic Execution Engine
:::caution

To use this feature, please update your `composer.json` by requiring `phpdocumentor/reflection-docblock` and `symfony/property-info` like in the `suggest` section. Enable the `PimcoreGenericExecutionEngineBundle` in your `bundles.php` file and install it accordingly with the following command:


`bin/console pimcore:bundle:install PimcoreGenericExecutionEngineBundle`

:::

Generic execution engine
- executes jobs asynchronously via [Symfony Messenger](https://symfony.com/doc/current/messenger.html).
- traces and logs state of job runs.
- manages (start, cancel, restart) job runs.

The execution is based on the [Symfony Messenger](https://symfony.com/doc/current/messenger.html#consuming-messages-running-the-worker) queue.
If activated, the selected jobs are automatically executed.

:::caution

Messages are dispatched via `pimcore_generic_execution_engine` transport. Please ensure you have workers processing this transport.

:::