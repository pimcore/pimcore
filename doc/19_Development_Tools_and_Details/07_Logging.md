# Logging

There are several different kinds of logs in Pimcore. All of them are located under `/var/log` and get rotated
as well as compressed automatically on every day (7 days retention) by the maintenance command. 
 
## `<env>.log`
This is definitely one of the most important logs and also the default logging location. 

Pimcore uses Symfony default monolog logging with following channels: `pimcore`, `pimcore_api`, `session`. 
For details see [Symfonys monolog docs](https://symfony.com/doc/current/logging.html).

## `php.log`
By default Pimcore writes PHP-Engine Log Messages to the file `php.log`.
You can change this by add the following to your symfony config:
```yaml
monolog:
    handlers:
        error:
            type: stream
            path: "%kernel.logs_dir%/own_php.log"
            level: error
```

## usagelog.log
In this log you can find every action done within the Pimcore Backend Interface. It can be deactivated by configuring `disable_usage_statistics` in `config/config.yaml`:

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
Sometimes it's necessary to debug redirects, for example when a redirect ends in an infinite loop. 
In this log you can see every request where a redirect takes action. 

##### Example
```
2021-04-26T14:03:20+0200 : 10.242.2.255          Custom-Redirect ID: 1, Source: /asdsad/redirectsource/asd -> /en/Events
```

:::info

Redirects are logged into a `redirect` monolog log channel at info level. By default, Pimcore logs that channel into `var/log/redirect.log`.
Of course, the corresponding monolog handler configuration can be adapted as needed.

:::

## Writing Your Own Log Files
To create a custom log entry, we need to create the monolog log channels and log handlers configuration. Here is an example on how to log in a custom filename called `custom.log`

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
It is possible to inject the `Psr\Log\LoggerInterface` by changing the variable name eg. `$customLogLogger` (camel case channel name + `Logger`) and Symfony will automatically wire the specified channel.

```php
class SomeService {
    public function __construct(\Psr\Log\LoggerInterface $customLogLogger)
    {
        $customLogLogger->debug('Test Message');
    }
}
```

For more, please refer to [Monolog Documentation](https://symfony.com/doc/6.2/logging/channels_handlers.html)
