# Loading service definitions from within a bundle
 
If you want to load services from your bundle instead of having to define them in `config/services.yaml` you need to 
create a dependency injection extension which is able to load your service definitions. You can find detailed documentation
on this topic here: [Extensions Documentation](https://symfony.com/doc/5.2/bundles/extension.html).

As an example, we want to create an extension for the `App` which is able to load a `config/services.yaml`
config file.

First, we need to create an extension class. The extension class name follows the convention that it is located in
the `DependencyInjection` sub-namespace and that it is named the same as the bundle class, but with `Bundle` replaced with
`Extension`. So for your `MyBundle`, we'll create the following extension:

```php
<?php
// src/MyBundle/DependencyInjection/AppExtension.php

namespace MyBundle\DependencyInjection;

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
            // looks in src/MyBundle/Resources/config
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        // load services.yaml
        $loader->load('services.yaml');
        
        // more load() calls as needed...
    }
}
```

Next, we create the config file we're trying to load:

```yaml
# config/services.yaml

services:
    my_custom_class:
        class: MyBundle\Custom\Class
```

The `services.yml` should now automatically be loaded and register the `my_custom_class` service on the container.
