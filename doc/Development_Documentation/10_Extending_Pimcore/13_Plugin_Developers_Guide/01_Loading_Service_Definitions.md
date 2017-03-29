# Loading service definitions from within a bundle
 
If you want to load services from your bundle instead of having to define them in `app/config/services.yml` you need to 
create a dependency injection extension which is able to load your service definitions. You can find detailed documentation
on this topic here: [Extensions Documentation](http://symfony.com/doc/current/bundles/extension.html).

As an example, we want to create an extension for the `AppBundle` which is able to load a `Resources/config/services.yml`
config file.

First, we need to create an extension class. The extension class name follows the convention that it is located in
the `DependencyInjection` sub-namespace and that it is named the same as the bundle class, but with `Bundle` replaced with
`Extension`. So for your `AppBundle` which is living in `src/AppBundle/AppBundle.php` we'll create the following extension:

```php
<?php
// src/AppBundle/DependencyInjection/AppExtension.php

namespace AppBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AppExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // create a YamlFileLoader - this could also be a XmlFileLoader if you want to load XML 
        $loader = new YamlFileLoader(
            $container,
            // looks in src/AppBundle/Resources/config
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        // load services.yml
        $loader->load('services.yml');
        
        // more load() calls as needed...
    }
}
```

Next, we create the config file we're trying to load:

```yaml
# src/AppBundle/Resources/config/services.yml

services:
    my_custom_class:
        class: AppBundle\Custom\Class
```

The `service.yml` should now automatically be loaded and register the `my_custom_class` service on the container.
