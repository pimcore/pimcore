---
title: Logging
description: Log types, custom log channels, and Monolog configuration.
---

# Logging

Pimcore stores all logs under `/var/log`. The maintenance command rotates and compresses
them daily (7 days retention).

## `<env>-debug.log` / `<env>-error.log`

The primary log files. In development, Pimcore writes debug-level messages to
`dev-debug.log` and error-level messages to `dev-error.log`.
In production, only `prod-error.log` is written by default.

Pimcore uses Symfony's default Monolog logging with the following channels: `pimcore`, `pimcore_api`, `session`.
For details, see [Symfony's Monolog documentation](https://symfony.com/doc/current/logging.html).

## usage.log

Records every action performed in Pimcore Studio.
Deactivate it by configuring `disable_usage_statistics` in `config/config.yaml`:

```yaml
pimcore:
    general:
        disable_usage_statistics: true
```

##### Example Entry:
```
2021-04-26T13:18:35+0200 : 2|Pimcore\Bundle\AdminBundle\Controller\Admin\Document\PageController::saveAction|pimcore_admin_document_page_save|{"task":"publish","id":"1","data":"{\"cImage_0\":{\"data\":{\"id\":337,\"path\":\"\\\/..."}
2021-04-26T13:18:35+0200 : 2|Pimcore\Bundle\AdminBundle\Controller\Admin\Asset\AssetController::getImageThumbnailAction|pimcore_admin_asset_getimagethumbnail|{"id":"3","alt":"","height":"undefined","thumbnail":"portalCarousel","pimcore_editmode":"1"}
```

##### Explanation

| Value (from the example above)                                                   | Description                               |
|----------------------------------------------------------------------------------|-------------------------------------------|
| 2021-04-26T13:18:35+0200                                                         | Timestamp                                 |
| 2                                                                                | User-ID                                   |
| Pimcore\Bundle\AdminBundle\Controller\Admin\Document\PageController::saveAction  | Module\Controller::Action                 |
| pimcore_admin_document_page_save                                                 | Route name                                |
| \{"task":"pub .... \}                                                                | Request Parameters (shortened & censored) |

## redirect.log

Useful for debugging redirects, for example when a redirect ends in an infinite loop.
Every request where a redirect takes action is logged here.

##### Example
```
2021-04-26T14:03:20+0200 : 10.242.2.255          Custom-Redirect ID: 1, Source: /asdsad/redirectsource/asd -> /en/Events
```

:::info

Redirects are logged into a `redirect` Monolog channel at info level.
By default, Pimcore logs that channel into `var/log/redirect.log`.
Adapt the corresponding Monolog handler configuration as needed.

:::

## Writing Your Own Log Files

Create a custom log entry by configuring Monolog channels and handlers.
Example for a custom `custom.log` file:

```yaml
monolog:
    handlers:
        custom_handler:
            level:    debug
            type:     stream
            path:     '%kernel.logs_dir%/custom.log'
            channels: [custom_log]
    channels: [custom_log, some_other_channel]

```
Inject `Psr\Log\LoggerInterface` by naming the variable `$customLogLogger`
(camel-case channel name + `Logger`). Symfony automatically wires the specified channel.

```php
class SomeService {
    public function __construct(\Psr\Log\LoggerInterface $customLogLogger)
    {
        $customLogLogger->debug('Test Message');
    }
}
```

For more, refer to the [Monolog documentation](https://symfony.com/doc/current/logging/channels_handlers.html).
