# Events and Event Listeners

## General

Pimcore provides an extensive number of events that are fired during execution of Pimcore functions. These events can be 
used to hook into many Pimcore functions such as saving an object, asset or document and can be used to change or extend 
the default behavior of Pimcore.

The most common use-case for events is using them in a [bundle/extension](13_Bundle_Developers_Guide/06_Event_Listener_UI.md), but 
of course you can use them also anywhere in your code or in your dependency injection configuration (`config/services.yaml`). 

Pimcore implements the standard Symfony framework event dispatcher and just adds some pimcore specific events, 
so you can also subscribe to all Symfony core eventsand events triggered by arbitrary Symfony bundles. 

For that reason it's recommended to have a look into the Symfony [Events and Event Listeners documentation](https://symfony.com/doc/current/event_dispatcher.html)
first, which covers all basics in that matter. 

## Available Events

All Pimcore events are defined and documented as a constant on component specific classes: 
- [Assets](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/AssetEvents.php)
- [Documents](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/DocumentEvents.php)
- [Data Objects](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/DataObjectEvents.php)
- [Versions](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/VersionEvents.php)
- [Data Object Class Definition](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/DataObjectClassDefinitionEvents.php)
- [Object Brick Definition](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/ObjectbrickDefinitionEvents.php)
- [Data Object Classification Store](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/DataObjectClassificationStoreEvents.php)
- [Data Object Custom Layouts](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/DataObjectCustomLayoutEvents.php)
- [Data Object Import](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/Model/DataObjectImportEvent.php)
- [Data Object Quantity Value Unit](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/DataObjectQuantityValueEvents.php)
- [Users / Roles](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/UserRoleEvents.php)
- [Workflows](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/WorkflowEvents.php)
- [Elements](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/ElementEvents.php)
- [Mail](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/MailEvents.php)
- [Notifications](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/NotificationEvents.php)
- [Redirect](https://github.com/pimcore/pimcore/blob/11.x/bundles/SeoBundle/src/Event/RedirectEvents.php)
- [Admin](https://github.com/pimcore/admin-ui-classic-bundle/blob/1.x/src/Event/AdminEvents.php)
- [Frontend](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/FrontendEvents.php)
- [Cache](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/CoreCacheEvents.php)
- [Full-Page Cache](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/FullPageCacheEvents.php)
- [Search](https://github.com/pimcore/pimcore/blob/11.x/bundles/SimpleBackendSearchBundle/src/Event/SearchBackendEvents.php)
- [System](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/SystemEvents.php)
- [Tags](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/TagEvents.php)
- [Target Group](https://github.com/pimcore/personalization-bundle/blob/1.x/src/Event/TargetGroupEvents.php)
- [Targeting](https://github.com/pimcore/personalization-bundle/blob/1.x/src/Event/TargetingEvents.php)
- [Tests](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/TestEvents.php)
- [Translation](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/TranslationEvents.php)
- [Bundle Manager for injecting js/css files to Pimcore backend or editmode](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/BundleManagerEvents.php)

## Examples

### Hook into the pre-update event of assets, documents and objects
The following example shows how to register events for assets, documents and objects 

in your `config/services.yaml`: 
```yaml
services:
    App\EventListener\TestListener:
        tags:
            - { name: kernel.event_listener, event: pimcore.asset.preUpdate, method: onPreUpdate }
            - { name: kernel.event_listener, event: pimcore.document.preUpdate, method: onPreUpdate }
            - { name: kernel.event_listener, event: pimcore.dataobject.preUpdate, method: onPreUpdate }
```

in your listener class `src/EventListener/TestListener`
```php
<?php

namespace App\EventListener;
  
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DocumentEvent;

class TestListener
{
    public function onPreUpdate(ElementEventInterface $e): void
    {
        if ($e instanceof AssetEvent) {
            // do something with the asset
            $foo = $e->getAsset(); 
        } else if ($e instanceof DocumentEvent) {
            // do something with the document
            $foo = $e->getDocument(); 
        } else if ($e instanceof DataObjectEvent) {
            // do something with the object
            $foo = $e->getObject(); 
            $foo->setMyValue(microtime(true));
            // we don't have to call save here as we are in the pre-update event anyway ;-) 
        }
    }
}
```

### Hook into the list of objects in the tree, the grid list and the search panel

There are some global events having effect on several places. One of those is `pimcore.admin.object.list.beforeListLoad`.
The object list can be modified (changing condition for instance) before being loaded. This global event will apply to the tree, the grid list, the search panel and everywhere objects are listed in the Pimcore GUI.
This way, it is possible to create custom permission rules to decide if the object should or not be listed (for instance, the user must be the owner of the object, ...).

It extends the possibility further than just limiting list permissions folder by folder depending the user/role object workspace.
To ensure maximum security, it is advisable to combine this with an object DI to overload isAllowed method with the same custom permission rules. This way proper permission is ensuring all the way (rest services, ...).

### Hook into the Open Document|Asset|Data Object dialog

By the default, Pimcore tries to a resolve an element by its ID or path.
You can change this behavior by handling the [AdminEvents::RESOLVE_ELEMENT](https://github.com/pimcore/admin-ui-classic-bundle/blob/1.x/src/Event/AdminEvents.php) event
and implement your own logic.

```php
    \Pimcore::getEventDispatcher()->addListener(AdminEvents::RESOLVE_ELEMENT, function(ResolveElementEvent $event) {
        $id  = $event->getId();
        if ($event->getType() == "object") {
            if (is_numeric($event->getId())) {
                return;
            }

            $listing = new News\Listing();
            $listing->setLocale('en');
            $listing->setLimit(1);
            $listing->setCondition('title LIKE ' . $listing->quote('%' . $id . '%'));
            $listing = $listing->load();
            if ($listing) {
                $id = ($listing[0])->getId();
                $event->setId($id);
            }
        }               
```

### Asset Upload Path

Certain data types (like image, relations, etc ...) allow you to specify a dedicated upload path which defaults 
to '/_default_upload_bucket' if not otherwise specified in the config yml file or in the class definition.

The [AssetEvents::RESOLVE_UPLOAD_TARGET](https://github.com/pimcore/pimcore/blob/11.x/lib/Event/AssetEvents.php) event
allows you to dynamically modify the target path depending on the object it will be assigned to. 
Additional contextual information (like fieldname, fieldcollection index number, etc... ) could be utilized to
support the decision.

The contextual info provided is the same as described [here](../05_Objects/01_Object_Classes/01_Data_Types/10_Calculated_Value_Type.md):

Example Code: For the demo instance, this sample code would place an image which is dragged onto the image_1 field of object 6 (in-enim-justo_2)
into the /news/in-enim-justo_2/image_1 asset folder.

```php
        \Pimcore::getEventDispatcher()->addListener(AssetEvents::RESOLVE_UPLOAD_TARGET,
            function(\Pimcore\Event\Model\Asset\ResolveUploadTargetEvent $event) {
                $context = $event->getContext();
                if ($context["containerType"] == "object") {
                    $objectId = $context["objectId"];
                    $newsObject = News::getById($objectId);
                    if ($newsObject) {
                        $fieldname = $context["fieldname"];
                        $targetPath = $newsObject->getPath() . $newsObject->getKey() . "/" . $fieldname;
                        $parent = \Pimcore\Model\Asset\Service::createFolderByPath($targetPath);
                        if ($parent) {
                            $event->setParentId($parent->getId());
                        }
                    }

                }
        });
```
