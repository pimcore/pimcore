# Add your own Dependencies and Packages

Pimcore is 100% compatible to composer and it's very easy to use additional libraries 
installed via composer ([http://getcomposer.org/](http://getcomposer.org/)) in Pimcore. 

So additional functionality (like external libraries, ... ) needed in your application 
can be added and loaded. 


## Example
In this example we want to use some components of Symfony2 within our Pimcore project. 

#### Let's start: 
Go to the document-root of your project and just follow the usual composer setup. 
Add the desired package to the `composer.json` and define your dependencies: 
```php
{
    "require": {
        // ...
        // pimcore core dependencies
        // ...
        // your custom packages: ...
        "symfony/stopwatch": "*"
    }
}
```

Download and install composer from [https://getcomposer.org/download/](https://getcomposer.org/download/) 

#### Install dependencies:
```php
composer update
```

#### Use the newly added components

That's it! Now you can use all components of Symfony in your Pimcore project, no need to 
do anything further, it just works! 

Now you can use the installed libraries anywhere in your code. 

In this very basic example we use the Stopwatch component in an action: 
```php
<?php
 
use Pimcore\Controller\Action;
use Symfony\Component\Stopwatch\Stopwatch;
 
class TestController extends Action {
 
    public function stopwatchTestAction() {
 
        $stopwatch = new Stopwatch();
        // Start event named 'eventName'
        $stopwatch->start('eventName');
        // ... some code goes here
        $event = $stopwatch->stop('eventName');
 
        print_r($event);
 
        exit;
    }
 
...
````


## Pimcore Plugins
It is also possible to install Pimcore plugins via composer. To do so, the plugin has to have a `composer.json` with a special type and need to be accessably for composer. 

### Plugin `composer.json`
In the plugins `composer.json` a special type (`pimcore-plugin`) and a additional requirement (`"pimcore/installer-plugin": ">=1"`) has to be defined. These two things enable Pimcore to install plugins into */plugins* folder of Pimcore. 

```json 
{
  "name": "pimcore-plugins/MyPlugin",
  "type": "pimcore-plugin",
  "require": {
    "pimcore/installer-plugin": ">=1",
    ...
  }
}
```

### Include Plugins to Pimcore `composer.json`
To install a plugin into a Pimcore instance, just add the plugin as requirement to Pimcores `composer.json` as follows. Maybe you also need to add an additional repository to tell composer where to find the plugin. 

```json
{
    "require": {
        "pimcore-plugins/MyPlugin": "*"
    },
    "repositories": [
        { "type": "composer", "url": "https://composer-packages.mydomain.com/" }
    ]
}
```
