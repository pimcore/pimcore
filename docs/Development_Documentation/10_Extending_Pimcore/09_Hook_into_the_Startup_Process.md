# Hook into the Startup Process

It is possible to hook into the startup process of Pimcore. This is useful if you want to add some custom ZF routes to 
the controller front or to add extend the functionality without creating a plugin. 

To use the hook create a file called `startup.php` in `/website/config/` or just rename the existing `startup.php.example` 
to `startup.php`.

This file is included at the end `/pimcore/config/startup.php`.  

This hook is part of the general startup process, so this affects all modules, sapis (CLI, FCGI, mod_php, ...) and of 
course even the admin interface.
 

## Examples

You can find some examples in `/website/config/startup.php` too.
 
#### Add a custom controller plugin
```php
$front = \Zend_Controller_Front::getInstance();
$front->registerPlugin(new \Website\Controller\Plugin\Custom(), 700);
```

#### Add a custom event handler - hook into Pimcore without using a plugin
```php
\Pimcore::getEventManager()->attach("object.postAdd", function ($e) {
    $object = $e->getTarget();
    $object->getId();
    // do something with the object
});
```

#### Add a custom Zend Framework route
```php 
$front->addModuleDirectory('modulename');
 
$router = $front->getRouter();
$routeCustom = new \Zend_Controller_Router_Route(
    'custom/:controller/:action/*', [
        'module' => 'custom',
        'controller' => 'default',
        'action' => 'default'
    ]
);
$router->addRoute('custom', $routeCustom);
$front->setRouter($router);
 
//init the autoloader
\Pimcore::initAutoloader();
```

#### Add a custom module
```php 
<?php
/*
 * Overview of the module directory
 *
 *  + modules
 *    - company
 *      - controllers
 *      - lib
 *        - Company
 *      - models
 *      - views
 *
 * + plugins
 * + static
 * + website
 */
 
if (!defined("WEBSITE_MODULE_PATH"))  define("WEBSITE_MODULE_PATH", PIMCORE_DOCUMENT_ROOT . "/modules");
 
$front = \Zend_Controller_Front::getInstance();
$front->addModuleDirectory(PIMCORE_DOCUMENT_ROOT . "/modules");
 
 
//------------------------------------------------------------------------------------------------------------- Company
$autoloader = \Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Company');
 
set_include_path(implode(PATH_SEPARATOR, [
      WEBSITE_MODULE_PATH . '/Company/lib',
      get_include_path(),
]));
 
$resourceLoader = new \Zend_Application_Module_Autoloader([
      'namespace' => 'Company',
      'basePath' =>  WEBSITE_MODULE_PATH . "/company",
]);
 
 
$router = $front->getRouter();
 
$routeCompany = new \Zend_Controller_Router_Route(
    'company/:controller/:action/*',
    [
         'module'       => 'company',
         "controller"   => "default",
         "action"       => "default"
    ]
);
 
$router->addRoute("company", $routeCompany);
```

