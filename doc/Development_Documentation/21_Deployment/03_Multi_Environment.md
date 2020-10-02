# Multi Environment

Pimcore supports different configurations for different environments (dev, test, stage, prod, ...) as well as custom 
configurations including a fallback mechanism. 

## The `PIMCORE_ENVIRONMENT` variable

To switch to a different environment, you have to set the environment variable `PIMCORE_ENVIRONMENT` with a value of 
your choice (eg. development or test). `PIMCORE_ENVIRONMENT` is an equivalent of Symfony's standard `SYMFONY_ENV` so 
you can use whatever you prefer. 
 
Having the variable set, there is a special loading order for all configuration files. 

Loading example for `PIMCORE_ENVIRONMENT = dev`: 

```
app/config/pimcore/system_dev.yml
var/config/system_dev.yml
app/config/pimcore/system.yml
var/config/system.yml
```

The value of `PIMCORE_ENVIRONMENT` is used as a part of the file name separated by `_`.  If you haven't set the environment
variable, the loading order of configuration files looks like, below.

```
app/config/pimcore/system.yml
var/config/system.yml
```

> **Note:** If you put your configurations into `app/config/pimcore/` they might not writable by the Pimcore backend UI. 
> This can be especially useful when having automated building environments and don't want the user to allow changing settings.  

## Set a new Environment name

If you need add a new environment which is not an existing one by default (those are `prod`, `dev` and `test`) you need
to manually create a config file for the project in `app/config/config_<environment>.yml`.

> **Note:** The default `test` environment should only be used for running tests as it is configured to handle sessions 
> with the `session.storage.mock_file`. As consequence, logging into the admin interface is not possible in a browser context.

To create a new config, e.g. staging, which is based on the dev config, you must set `staging` as PIMCORE_ENVIRONMENT and
create the following config file `app/config/config_staging.yml`:

```yaml
imports:
    - { resource: '@PimcoreCoreBundle/Resources/config/pimcore/dev.yml' } # loads default dev configuration
    - { resource: config.yml }
```

By default, dev-bundles as the profiler are restricted to the `dev` environment. If you want to load those bundles in your
environment, you need to modify the kernel in `app\AppKernel.php` to include those bundles in your environment. Have a look
at the [Pimcore Kernel](https://github.com/pimcore/pimcore/blob/master/lib/Kernel.php#L189) to see what
is loaded in the default `dev` environment.

For instance for an new environment called `staging` using web profiler, you can add something like the following:

```php
<?php

use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Kernel;

class AppKernel extends Kernel
{
    public function registerBundlesToCollection(BundleCollection $collection)
    {
        $collection->addBundle(new \AppBundle\AppBundle);
    }

    protected function getEnvironmentsForDevBundles(): array
    {
        // override environments which should add dev bundles (e.g. the profiler)
        return ['dev', 'test', 'staging'];
    }
}
```

If you set `PIMCORE_ENVIRONMENT` to a new environment name before installation, all those steps need to be done before
running the installation.

## Setting the Environment

There are several ways to set the value of `PIMCORE_ENVIRONMENT` - depending on the environment and context you are using. 

### Apache mod_php

Add the following line to the virtual host configuration file.

```
SetEnv PIMCORE_ENVIRONMENT dev
```

### PHP FPM

Add the following line to your `pool.d` configuration file.

```
env[PIMCORE_ENVIRONMENT] = "dev"
```

### CLI

If you used a Unix system you would set the variable by CLI, like below.

```
PIMCORE_ENVIRONMENT="dev"; export PIMCORE_ENVIRONMENT
```

### Console Commands

When running `./bin/console` application, set the environment by `--env=dev`.
 
```
./bin/console --env=dev ...
```

### `.env`

Pimcore loads a `.env` file if it exists. See [DotEnv Component Documentation](https://symfony.com/doc/3.4/components/dotenv.html)
for details.

```
# .env
PIMCORE_ENVIRONMENT=dev
```

### Switching Environments Dynamically

Add this to your .htaccess to switch dynamically between your environments:

```
RewriteCond %{HTTP_HOST} ^localhost
RewriteRule .? - [E=PIMCORE_ENVIRONMENT:dev]
RewriteCond %{HTTP_HOST} ^staging\.site\.com$
RewriteRule .? - [E=PIMCORE_ENVIRONMENT:stage]
RewriteCond %{HTTP_HOST} ^www\.site\.com
RewriteRule .? - [E=PIMCORE_ENVIRONMENT:prod]
```

## Debug Mode & Environments

In order to include some specific debugging tools (profiler, toolbar, ...), Pimcore implicitly sets the 
environment to `dev` when enabling the debug mode in system settings and if **no** environment is defined manually as described
above.

## Influencing default behaviour

Pimcore ships with sensible defaults on which environment to use in which case, e.g. by automatically using the `dev` environment
if Pimcore is in debug mode and by automatically enabling the kernel debug flag for the `dev` and `test` environments. If
you need to influence that behaviour (e.g. because you have additional environments) you can do so by changing the environment
config during the [startup process](../01_Getting_Started/03_Configuration.md). For example, if you want to specify the
default environment to use when Pimcore's debug mode is enabled but no environment is explicitly defined:

```php
<?php

// /app/startup.php

/** @var \Pimcore\Config\EnvironmentConfig $environmentConfig */
$environmentConfig = \Pimcore\Config::getEnvironmentConfig();
$environmentConfig->setDefaultDebugModeEnvironment('prod');
```

## Supported Configurations

For examples, please see:

* <https://github.com/pimcore/demo/tree/master/app/config> 
* <https://github.com/pimcore/demo/tree/master/app/config/pimcore>
* <https://github.com/pimcore/demo/tree/master/var/config>
