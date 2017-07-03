##  Upgrade Notes for 4.6.0

#### Autoloader Optimization
In order to improve the performance of the autoloading process, 
the Zend Framework class loader was replaced by Composer's autoloader wherever it was possible. 
If you're using custom classes, namespaces or include-paths in your project, it's necessary to update your
`composer.json` before you perform the upgrade. 

##### Example
The following code in eg. your `startup.php` 
```php
$autoloader = \Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('YourVendorPrefix');
```
turns into in your `composer.json` (add to the existing autoloading configuration): 

```php
    "autoload": {
        "psr-4": {
            ...
            "YourVendorPrefix\\": [
                "website/lib/YourVendorPrefix",
                "website/models/YourVendorPrefix"
            ]
            ...
        },
        "psr-0": {
            ...
            "YourVendorPrefix_": [
                "website/lib/YourVendorPrefix",
                "website/models/YourVendorPrefix"
            ]
            ...
        }
```


##  Upgrade notes for 4.4.0
- Dropped support for PHP 5.5 (reached end of life)
- Memcached adapter is deprecated, please use Redis2 instead. 
- Whitespace and uppercase letters are now allowed in element's keys, to keep the old policy, add the following code to your `startup.php` : 
```php 
\Pimcore::getEventManager()->attach("system.service.preGetValidKey", function (\Zend_EventManager_Event $event) {
    $key = $event->getParam("key");
    $key = \Pimcore\File::getValidFilename($key);
    return $key;
});
```

More examples like a type specific configuration can be found [here](https://github.com/pimcore/pimcore/issues/898#issuecomment-251909498). 

- Document editable 'Date': if using Carbon (DateTime) the syntax for the config-option `format` changed from `date()` to `strftime()`
- Keys and filenames starting or ending with `.` are no longer allowed. 

## Upgrade notes for 4.3.0
#### Newsletter
Newsletters are no longer managed in Marketing. 
See: [Newsletter Documents](../../08_Tools_and_Features/19_Newsletter.md) (since 4.3.0) 

#### Class definitions are stored as PHP configuration files
The definition (fields + layout) is no longer stored in *.psf files (PHP serialized format) but is migrated to PHP configuration files and is using the class name in the file name instead of the ID. 
This brings some huge advantages when working with a VCS like Git, because it's easier to merge definitions and keep track of the history since it uses well formed PHP code. Additionally there's a summary of all the fields used in the definition in the DocBlock. 
See also: [Example definition](https://github.com/pimcore/pimcore/blob/pimcore4/website_demo/var/classes/definition_blogArticle.php)

## Upgrade notes for 4.2.0
#### Class Mapping / Dependency Injection
Class mapping feature was replaced by PHP-DI solution. The updater automatically migrates your classmap.php to di.php so basically there's nothing special to consider (except eventually downloading & adding di.php to your VCS). 
For more details please have a look at the following pages: 
- Dependency Injection (since 3866)
- Overwrite Pimcore models using dependency injection (since build 3866)

#### Removed Methods / Classes / Interfaces
The following legacy methods were removed: 
```php
Asset::getConcreteById()
Document::getConcreteById()
Document::getConcreteByPath()
```

The following legacy classes / interfaces were removed: 
`Pimcore\Model\Document\DocumentInterface`

## Upgrade notes for 4.1.2
#### Using Carbon instead of DateTime
Pimcore works now with Carbon date objects, since Carbon extends DateTime there's nothing special to consider.
http://carbon.nesbot.com/docs/ 

## Upgrade notes for 4.0.1
#### Composer
As of 4.0.1 composer is a dependency of Pimcore. Please ensure composer is installed properly (in PATH) on the system.  

There was also a bug in the updater prior 4.0.1 which caused the corruption of the composer.json and autoload class map.
If you encounter any problems please try the following on the command line:  
```
cd path/to/document-root
rm composer.lock
composer update nothing
``` 

#### Mcrypt  dependency
Mcrypt is no longer a dependency. Reason: Mcrypt will be removed in PHP 7.1.

#### Document Pages: Field "keywords" is removed
If you need any content out of this field, you can find them in `/website/var/system/document-page-keyword-export.csv`

