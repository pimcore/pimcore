# Application logger

## General

The application logger is a tool, which developers can use to log certain events and errors within a pimcore powered application. 

<div class="inline-imgs">

The logs are visible and searchable within the Pimcore backend GUI ![Tools menu](../img/Icon_tools.png) **Tools -> Application Logger**:

</div>

![Application logger menu](..//img/applogger_menu.png)


![Application logger preview](../img/applogger_backend_preview.png)

## How to create log entries

The application logger is a PSR-3 compatible component and therefore it can be used the usual way:

### Basic usage - example

```php
$logger = \Pimcore\Log\ApplicationLogger::getInstance("my component", true); // returns a PSR-3 compatible logger
$logger->error("this is just a simple test");
$logger->alert("another message");
$logger->emergency("this is just a simple test");
 
$logger->log("info", "my message");
```

### Advanced usage - example

```php
$logger = new \Pimcore\Log\ApplicationLogger();
$logger->setComponent("example");
  
// addWriter() takes PSR-3 compatible loggers, Monolog handlers and ZF writers
  
// standard application logger writer (database)
$logger->addWriter(new \Pimcore\Log\Handler\ApplicationLoggerDb());
 
//additional log writer (ZF1)
$logger->addWriter(new \Zend_Log_Writer_Stream('php://output'));
 
//additional log handler (Monolog)
$logger->addWriter(new \Monolog\Handler\StreamHandler('php://output'));
  
//additional logger (PSR-compatible)
$customLog= new \Monolog\Logger('test');
$customLog->pushHandler(new \Monolog\Handler\StreamHandler('php://output'));
$logger->addWriter($customLog);
```

### Attaching additional data - example

There are some context variables with a special functionality: fileObject, relatedObject, component.

```php
$logger = \Pimcore\Log\ApplicationLogger::getInstance("my component", true); // returns a PSR-3 compatible logger
 
$fileObject = new \Pimcore\Log\FileObject("some interesting data");
$myObject = \Pimcore\Model\Object\AbstractObject::getById(73);
 
$logger->error("my error message", [
    "fileObject" => $fileObject,
    "relatedObject" => $myObject, 
    "component" => "different component"
]);
```

In the application logger grid, the new row was created: *my error message* with related object. 

If you click on the row you can go to the object editor by clicking on the *Related object* edit icon in the popup.

![App logger popup](../img/applogger_backend_popup.png)

### Setting an individual logger level

Adds a console logger and sets the minimum logging level to *INFO* (overwrites log level in Pimcore system settings):

```php

$logger = \Pimcore\Log\ApplicationLogger::getInstance("my component", true); // returns a PSR-3 compatible logger
 
$customLog= new \Monolog\Logger('SAP_exporter');
$customLog->pushHandler(new \Monolog\Handler\StreamHandler('php://output', \Monolog\Logger::INFO));
$logger->addWriter($customLog);
```

## Configuration

There are some options in the system settings to configure the application logger (within the *Debug* panel):

![Application logger settings](../img/applogger_settings.png)

When the *Send log summary per mail* checkbox is activated the defined receivers will receive log entries by mail. 
The priority can be used to setup which log messages will be contained in the mail. 
For example errors are more important than just info entries. 

The archive function automatically creates new database tables to archive the log entries in the form `application_logs_archive_*`. 
In the above example log entries will be moved after 30 days to these archive tables. 
Optionally a different database name for the archive tables can be defined. 

