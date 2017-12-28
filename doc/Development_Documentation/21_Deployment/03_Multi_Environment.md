# Multi Environment

Pimcore supports different configurations for different environments (dev, test, stage, prod, ...) as well as custom 
configurations including a fallback mechanism. 


## The `PIMCORE_ENVIRONMENT` Variable
To switch to a different environment, you have to set the environment variable `PIMCORE_ENVIRONMENT` with a value of 
your choice (eg. development or test). `PIMCORE_ENVIRONMENT` is an equivalent of Symfony's standard `SYMFONY_ENV` so 
you can use whatever you prefer. 
 
Having the variable set, there is a special loading order for all configuration files. 

Loading example for `PIMCORE_ENVIRONMENT = dev`: 

```
app/config/pimcore/system_dev.php
var/config/system_dev.php
app/config/pimcore/system.php
var/config/system.php
```

The value of `PIMCORE_ENVIRONMENT` is used as a part of the file name separated by `_`. 


If you haven't set the environment variable, the loading order of configuration files looks like, below.

```
app/config/pimcore/system.php
var/config/system.php
```

> **Note:** If you put your configurations into `app/config/pimcore/` they might not writable by the Pimcore backend UI. 
> This can be especially useful when having automated building environments and don't want the user to allow changing settings.  

## Set a new Environment name

You can need to configure a new environment name different than the existing ones, which respect Symfony convention: `dev`, `test` and `prod`.

> **Note:** The default `test` environment should only be used for running test suite (like Travis).
> It use the session.storage.mock_file which fakes sessions (as consequence, the administrator cannot log in Pimcore for instance)

To configure a new environment, you need to manually create a YAML config file for the project in:

```
app/config/pimcore/
```

As a starting point, you can inspire your new YAML config file from the existing YAML config files for dev and prod environment in `app/config/pimcore/` and `pimcore/lib/Pimcore/Bundle/CoreBundle/Resources/config/pimcore/`.

For instance, the most minimal config, reproducting prod environment, will be:

```
imports:
    - { resource: config.yml }

monolog:
    handlers:
        main:
            type:         fingers_crossed
            action_level: error
            buffer_size: 2000
            handler:      nested
        nested:
            type:  stream
            path:  "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
        console:
            type:  console
```

A config file, reproducting dev environment (and using specific bundles, see below how to register them) will be like this:

```
imports:
    - { resource: config.yml }

framework:
    router:
        resource: "%kernel.root_dir%/config/routing_dev.yml"
        strict_requirements: true
    profiler: { only_exceptions: false }

web_profiler:
    toolbar: true
    intercept_redirects: false

monolog:
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
            channels: ["!event"]
        console:
            type:   console
            channels: ["!event"]

pimcore:
    error_handling:
        render_error_document: false

    web_profiler:
        toolbar:
            excluded_routes: ~

    translations:
        # enables support for the pimcore_debug_translations magic parameter
        # see https://pimcore.com/docs/5.0.x/Development_Tools_and_Details/Magic_Parameters.html
        debugging:
            enabled: true
```

If you use some specific bundles, you need to register them in `app\AppKernel.php`.
For instance for an new environment called `staging` using web profiler, you should add in the method `registerBundlesToCollection`:
```
use Pimcore\Bundle\GeneratorBundle\PimcoreGeneratorBundle;
use Sensio\Bundle\DistributionBundle\SensioDistributionBundle;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
...
	public function registerBundlesToCollection(BundleCollection $collection)
	{
		//...
		
		// environment specific bundles
		if (in_array($this->getEnvironment(), ['staging'])) {
			$collection->addBundles([
				new DebugBundle(),
				new WebProfilerBundle(),
				new SensioDistributionBundle()
			], 80);
			// add generator bundle only if installed
			if (class_exists('Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle')) {
				$collection->addBundle(
					new SensioGeneratorBundle(),
					80, // priority
					['dev'] // will use the dev config files of the bundle
				);
				// PimcoreGeneratorBundle depends on SensioGeneratorBundle
				$collection->addBundle(
					new PimcoreGeneratorBundle(),
					60,
					['dev']
				);
			}
		}

		//...
	}
```

If you set `PIMCORE_ENVIRONMENT` to a new environment name before installation, all those steps need to be done before running the installation.

## Set the Environment

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

### Debug Mode & Environments

In order to include some specific debugging tools (profiler, toolbar, ...), Pimcore implicitly sets the 
environment to `dev` when enabling the debug mode in system settings and if **no** environment is defined manually as described above. 

## Supported Configurations

For examples, please see: 
* <https://github.com/pimcore/pimcore/tree/master/app/config> 
* <https://github.com/pimcore/pimcore/tree/master/app/config/pimcore>
* <https://github.com/pimcore/pimcore/tree/master/var/config>
