# Multi Environment

Pimcore supports different configurations for different environments (dev, test, stage, prod, ...) as well as custom 
configurations including a fallback mechanism. 


## The `PIMCORE_ENVIRONMENT` Variable
To switch to a different environment you have to set the environment variable `PIMCORE_ENVIRONMENT` with a value of 
your choice (eg. development or test).
 
Having the variable set, there is a special loading order for all configuration files. 

Loading example for `PIMCORE_ENVIRONMENT = development`: 

```
website/config/system.development.php
website/var/config/system.development.php
website/config/system.php
website/var/config/system.php
```

The value of `PIMCORE_ENVIRONMENT` is used as a part of the file name.


If you haven't set the environment variable, the loading order of configuration files looks like, below.

```
website/config/system.php
website/var/config/system.php
```

> **Note:** If you put your configurations into `website/config` they are also not writable by the Pimcore backend UI. 
> This can be especially useful when having automated building environments and don't want the user to allow changing settings.  


## Set the Environment

There are several ways to set the value of `PIMCORE_ENVIRONMENT` - depending on the environment and context you are using. 


### Apache mod_php

Add the following line to the virtual host configuration file.

```
SetEnv PIMCORE_ENVIRONMENT development
```


### PHP FPM

Add the following line to your `pool.d` configuration file.

```
env[PIMCORE_ENVIRONMENT] = "development"
```

### CLI

If you used a Unix system you would set the variable by CLI, like below.

```
PIMCORE_ENVIRONMENT="development"; export PIMCORE_ENVIRONMENT
```

### `console.php` Commands

When running `pimcore/cli/console.php` application, set the environment by `--environment=development`.
 
```
php /path/to/pimcore/cli/console.php --environment=development ...
```

### Switching Environments Dynamically

Add this to your .htaccess to switch dynamically between your environments:

```
RewriteCond %{HTTP_HOST} ^localhost
RewriteRule .? - [E=PIMCORE_ENVIRONMENT:Development]
RewriteCond %{HTTP_HOST} ^staging\.site\.com$
RewriteRule .? - [E=PIMCORE_ENVIRONMENT:Staging]
RewriteCond %{HTTP_HOST} ^www\.site\.com
RewriteRule .? - [E=PIMCORE_ENVIRONMENT:Production]
```


## Supported Configurations

For examples, please see: 
[https://github.com/pimcore/pimcore/tree/master/website_demo/var/config](https://github.com/pimcore/pimcore/tree/master/website_demo/var/config)
 
 