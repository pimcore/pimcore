# Logging

There are several different kinds of logs in Pimcore. All of them are located under `/var/logs/`.
 
## debug.log
This is definitely one of the most important logs and also the default logging location. 

You can configure the log levels in Pimcore Backend Interface in *Settings* > *System* > *Debug*. 
For development purposes it's recommenced to turn on all log-levels. 

If you turn on the DEV-MODE also the SQL-profiler logs to this location. 

The log file will be rotated and compressed if it gets larger than 200 MB. The archived logs 
will be kept for 30 days.


## usagelog.log
In this log you can find every action done within the Pimcore Backend Interface. 

##### Example Entry: 
``` 
2013-07-25T18:26:30+02:00 : 2|admin|page|save|{"task":"publish","id":"4","data":"{\"headTitle\":{\"data\":\"Getting started\",\"..."}
```

##### Explanation

| Value (from the example above) | Description |
| ------------------------------ | ----------- |
| 2013-07-25T18:26:30+02:00 | Timestamp |
| 2 | User-ID |
| admin | Module (MVC) |
| page | Controller (MVC) |
| save | Action (MVC) |
| {"task":"pub .... | Request Parameters (shortened & censored) |


## redirect.log
Sometimes it's necessary to debug redirects, for example when a redirect ends in an infinite loop. 
In this log you can see every request where a redirect takes action. 

##### Example
```
2013-08-12T19:49:43+02:00 : 10.242.2.255         Source: /asdsad/redirectsource/asd -> /basic-examples
```

## dbprofile-*.log
Contains DB profiles for a single request. See [Magic Parameters](./15_Magic_Parameters.md) for more details. 


## Writing your own log files
You can add your own logging functionality using Pimcore's log writer. You can call a static 
function like this:

##### Custom log entry
```php
\Pimcore\Log\Simple::log($name, $message);
```

The `$name` variable defines the filename of the log file, "mylog" will write a file to `/var/logs/mylog.log` 
(extension is added automatically). If the file does not yet exist it will be created on the fly. 

The message is the line that will be written to the log. A date and time will also be prepended 
automatically to each log entry.


