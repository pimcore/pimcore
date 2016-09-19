# Event API and Event Manager

## General

Pimcore provides an extensive number of events that are fired during execution of Pimcore functions. These events can be 
used to hook into many Pimcore functions such as saving an object, asset or document and can be used to change or extend 
the default behavior of Pimcore.

The most common use-case for events is using them in a [plugin](./13_Plugin_Developers_Guide/03_Plugin_Backend.md), but 
of course you can use them also anywhere in your code or when hooking into the startup process. 

You can attach a handler at any time in your code by using the following code:

```php 
\Pimcore::getEventManager()->attach("object.postAdd", function (\Zend_EventManager_Event $e) {
    $object = $e->getTarget();
    $object->getId();
    // ...
});
```

The Pimcore event API is based on the ZF Event Manager. 
[Click here to learn more about attaching listeners to an event.](http://framework.zend.com/manual/1.12/de/zend.event-manager.event-manager.html)

<div class="notice-box">
IMPORTANT INFO
`Pimcore::getEventManager()` returns an instance of `Zend_EventManager_EventManager` and therefore it provides the full
 set of functionalities that the ZF provides. 
For details have a look at [http://framework.zend.com/manual/1.12/de/zend.event-manager.event-manager.html](http://framework.zend.com/manual/1.12/de/zend.event-manager.event-manager.html) 
</div>


## Examples
The following example shows how to register events for assets, documents and objects where the event-handler is in a custom class. 

```php
<?php
namespace Website\Custom;
  
use Pimcore\Model;
class Extension {
     
    public function handleCreate (\Zend_EventManager_Event $e) {
        $element = $e->getTarget();
        if($element instanceof Model\Asset) {
            // do something with the asset
        } else if ($element instanceof Model\Document) {
            // do something with the document
        } else if ($element instanceof Model\Object\AbstractObject) {
            // do something with the object
        }
    }
     
    public function handleDelete(\Zend_EventManager_Event $e) {
        $element = $e->getTarget();
        // do something with the element
    }
}
$extension = new Website_Custom_Extension();
foreach (["asset","object","document"] as $type) {
    Pimcore::getEventManager()->attach($type . ".postAdd", [$extension, "handleCreate"]);
    Pimcore::getEventManager()->attach($type . ".postDelete", [$extension, "handleDelete"]);
}
```


The following example shows how to deal with event parameters and a static callback method.
```php
<?php
namespace Website\Auth;
class Handler {
     
    public static function logout (\Zend_EventManager_Event $e) {
        $user = $e->getParam("user");
        // user is now an instance of User
         
        // do something with the user
        Logger::info("User with ID " . $user->getId() . " left the pimcore admin interface");
    }
}
\Pimcore::getEventManager()->attach("admin.login.logout", ["\Website\Auth\Handler", "logout"]);
```


This example show how to use an anonymous callback and a specific priority (87) 
```php
<?php
$myControllerPlugin = new \Website\Controller\Plugin\MyCustomPlugin();
$myControllerPlugin->someMethod();
 
\Pimcore::getEventManager()->attach("system.startup", function (\Zend_EventManager_Event $e) use ($myControllerPlugin) {
    $frontController = $e->getTarget();
    $frontController->registerPlugin($myControllerPlugin);
}, 87);
```

attach multiple events
```php
\Pimcore::getEventManager()->attach(["object.postAdd","object.postUpdate"], function (\Zend_EventManager_Event $e) {
    $object = $e->getTarget();
    $object->getId();
    // ...
});
```


## Available Events

### System / General

| Name | Target | Parameters | Description | 
| ---- | ------ | ---------- | ----------- |
| `system.startup` | `Zend_Controller_Front` | | This event is fired on startup, just before the MVC dispatch starts. |
| `system.shutdown` | | - | This event is fired on shutdown (register_shutdown_function)|
| `system.maintenance` | `Pimcore\Model\Schedule\Manager\Procedural` or `Pimcore\Model\Schedule\Manager\Daemon` | - | Use this event to register your own maintenance jobs, this event is triggered just before the jobs are executed |
| `system.console.init` | `Pimcore\Console\Application` | | See Console / CLI |
| `system.di.init` | `DI\ContainerBuilder` | | Fires when the DI is built |
| `system.maintenance.activate` | | | This event is fired on maintenance mode activation |
| `system.maintenance.deactivate` | | | This event is fired on maintenance mode deactivation |
| `system.cache.clearOutputCache` | | | This event is fired on Output Cache clear |
| `system.cache.clear` | | | This event is fired on Cache clear |
| `system.cache.clearTemporaryFiles` | | | This event is fired on Temporary Files clear |

### Document

| Name | Target | Parameters | Description | 
| ---- | ------ | ---------- | ----------- |
| `document.preAdd` | `Pimcore\Model\Document` | - |  |
| `document.postAdd` | `Pimcore\Model\Document` | - | |
| `document.preUpdate` | `Pimcore\Model\Document` | (bool) `saveVersionOnly` | saveVersionOnly is set if method saveVersion() was called instead of save() |
| `document.postUpdate` | `Pimcore\Model\Document` | (bool) `saveVersionOnly` | saveVersionOnly is set if method saveVersion() was called instead of save() |
| `document.preDelete` | `Pimcore\Model\Document` | - |  |
| `document.postDelete` | `Pimcore\Model\Document` | - |  |
| `document.print.postPdfGeneration` | `Pimcore\Model\Document` | (string) `filename`, (string) `pdf` | filename contains the filename of the generated pdf on filesystem, pdf contains generated pdf as string. |

### Object

| Name | Target | Parameters | Description | 
| ---- | ------ | ---------- | ----------- |
| `object.postAdd` | `Pimcore\Model\Object\AbstractObject` | - | |
| `object.postDelete` | `Pimcore\Model\Object\AbstractObject` | - | |  
| `object.postUpdate` | `Pimcore\Model\Object\AbstractObject` | (bool) saveVersionOnly | saveVersionOnly is set if method saveVersion() was called instead of save() |
| `object.preAdd` | `Pimcore\Model\Object\AbstractObject` | - |  |
| `object.preDelete` | `Pimcore\Model\Object\AbstractObject` | - | |  
| `object.preUpdate` | `Pimcore\Model\Object\AbstractObject` | (bool) saveVersionOnly | saveVersionOnly is set if method saveVersion() was called instead of save() |


### Asset

| Name | Target | Parameters | Description | 
| ---- | ------ | ---------- | ----------- |
| `asset.preAdd` | `Pimcore\Model\Asset` | - |  |
| `asset.postAdd` | `Pimcore\Model\Asset` | -  | |
| `asset.preUpdate` | `Pimcore\Model\Asset` | (bool) `saveVersionOnly` | saveVersionOnly is set if method saveVersion() was called instead of save() |
| `asset.postUpdate` | `Pimcore\Model\Asset` | (bool) `saveVersionOnly` | saveVersionOnly is set if method saveVersion() was called instead of save() |
| `asset.preDelete` | `Pimcore\Model\Asset` | - |  |
| `asset.postDelete` | `Pimcore\Model\Asset` | - |   |
| `asset.image.thumbnail` | `Pimcore\Model\Asset\Image\Thumbnail` | (bool) `deferred`, (bool) `generated` | fires after the thumbnail was created |
| `asset.video.image-thumbnail` | `Pimcore\Model\Asset\Video\ImageThumbnail` | (bool) `deferred`, (bool) `generated` | fires after the image thumbnail was created |
| `asset.document.image-thumbnail` | `Pimcore\Model\Asset\Document\ImageThumbnail` | (bool) `deferred`, (bool) `generated` | fires after the image thumbnail was created |


### Object Class

| Name | Target | Parameters | Description | 
| ---- | ------ | ---------- | ----------- |
| `object.class.postAdd` | `Pimcore\Model\Object\ClassDefinition` | - |  |
| `object.class.postUpdate` | `Pimcore\Model\Object\ClassDefinition` | - |  |
| `object.class.preAdd` | `Pimcore\Model\Object\ClassDefinition` | - |  |
| `object.class.preUpdate` | `Pimcore\Model\Object\ClassDefinition` | - |  |


### Object KeyValue Group Configuration

| Name | Target | Parameters | Description | 
| ---- | ------ | ---------- | ----------- |
| `object.keyValue.groupConfig.postAdd` | `Pimcore\Model\Object\KeyValue\GroupConfig` | - |  |
| `object.keyValue.groupConfig.postUpdate` | `Pimcore\Model\Object\KeyValue\GroupConfig` | - |  |
| `object.keyValue.groupConfig.postUpdate` | `Pimcore\Model\Object\KeyValue\GroupConfig` | - |  |
| `object.keyValue.groupConfig.postDelete` | `Pimcore\Model\Object\KeyValue\GroupConfig` | - |   |
| `object.keyValue.groupConfig.preAdd` | `Pimcore\Model\Object\KeyValue\GroupConfig` | - |  |
| `object.keyValue.groupConfig.preDelete` | `Pimcore\Model\Object\KeyValue\GroupConfig` | - | |  
| `object.keyValue.groupConfig.preUpdate` | `Pimcore\Model\Object\KeyValue\GroupConfig` | - | | 




### Object KeyValue Key Configuration

### Versions

### Search Backend

### Admin Interface

### Frontend

### Workflow Management

## Example of custom error handling using the Event API

This will prevent the user from saving News objects.

```php
// website/var/config/startup.php
  
<?php
\Pimcore::getEventManager()->attach("object.preUpdate", function ($event) {
    $object = $event->getTarget();
    if ($object instanceof Pimcore\Model\Object\News) {
        throw new \Pimcore\Model\Element\ValidationException("YOU are NOT allowed to save such kind of objects, go away !!!", 2412);
    }
});
```

The error will be presented in a different way.

![Plugin Error Handling](../img/plugin-error-handling.jpg)

* orange: the error code
* green: error message
* blue: link to the stack trace