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

To create a new config, e.g. staging, which is based on the dev config, you must set `staging` as PIMCORE_ENVIRONMENT and create the following config file `app/config/config_staging.yml`:

```
imports:
    - { resource: '@PimcoreCoreBundle/Resources/config/pimcore/dev.yml' }
    - { resource: config.yml }
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
					['staging'] // will use the staging config files of the bundle
				);
				// PimcoreGeneratorBundle depends on SensioGeneratorBundle
				$collection->addBundle(
					new PimcoreGeneratorBundle(),
					60,
					['staging']
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
