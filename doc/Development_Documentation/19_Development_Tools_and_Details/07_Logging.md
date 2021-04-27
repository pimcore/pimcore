# Logging

There are several different kinds of logs in Pimcore. All of them are located under `/var/log` and get rotated
as well as compressed automatically on every day (7 days retention) by the maintenance command. 
 
## <env>.log
This is definitely one of the most important logs and also the default logging location. 

Pimcore uses Symfony default monolog logging with following channels: `pimcore`, `pimcore_api`, `session`. 
For details see [Symfonys monolog docs](http://symfony.com/doc/3.4/logging.html).

## php.log
By default Pimcore writes PHP-Engine Log Messages to the file `php.log`.
You can change this using constant `PIMCORE_PHP_ERROR_LOG` that is used to set PHP's [error_log Configuration](http://php.net/manual/en/errorfunc.configuration.php#ini.error-log).

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
| {"task":"pub ....                                                                | Request Parameters (shortened & censored) |

## redirect.log
Sometimes it's necessary to debug redirects, for example when a redirect ends in an infinite loop. 
In this log you can see every request where a redirect takes action. 

##### Example
```
2021-04-26T14:03:20+0200 : 10.242.2.255          Custom-Redirect ID: 1, Source: /asdsad/redirectsource/asd -> /en/Events
```

## Writing your own log files
You can add your own logging functionality using Pimcore's log writer. You can call a static 
function like this:

##### Custom log entry
```php
\Pimcore\Log\Simple::log($name, $message);
```

The `$name` variable defines the filename of the log file, "mylog" will write a file to `/var/log/mylog.log` 
(extension is added automatically). If the file does not yet exist it will be created on the fly. 

The message is the line that will be written to the log. A date and time will also be prepended 
automatically to each log entry.


