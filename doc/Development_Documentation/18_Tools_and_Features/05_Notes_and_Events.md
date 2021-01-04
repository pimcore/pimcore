# Notes & Events

## General
Notes & Events are primarily used to log changes or events on elements independently from the versioning.
This includes changes made by marketers, editors, automated importers / synchronisations, .... 
Simply everything that has nothing to do with the data itself but is important to know. 

## Use cases

* An importer (CLI-script) that adds information to objects which changes were made
* Marketers / SEOs adding information which changes were made on documents like *"optimized for keyword xyz ..."*

There are really nearly endless possibilities what to do with Notes & Events.

## Create Notes & Events

### Using API

```php
use Pimcore\Model;

$object = Model\DataObject\AbstractObject::getById(4);

$note = new Model\Element\Note();
$note->setElement($object);
$note->setDate(time());
$note->setType("erp_import");
$note->setTitle("changed availabilities to xyz");
$note->setUser(0);

// you can add as much additional data to notes & events as you want
$note->addData("myText", "text", "Some Text");
$note->addData("myObject", "object", Model\DataObject\AbstractObject::getById(7));
$note->addData("myDocument", "document", Model\Document::getById(18));
$note->addData("myAsset", "asset", Model\Asset::getById(20));

$note->save();
```

And this is how the entry looks like:

![Notes & events - the grid preview](../img/notesandevents_preview.png)


### Add Events in Pimcore backend UI

You could also add the note directly in the edit view of objects, documents and assets.

![Notes & events - add a note manually](../img/notesandevents_add_note.png)


#### Specify Custom Types for Notes and Events

Via Pimcore configuration, the selectable types for notes and events can be specified per content type (asset, document, 
data object), see sample config below:

```yml
# app/config/config.yml or any other Symfony config file

pimcore_admin:
    documents:
        notes_events:
            types:
                - ""
                - "content"
                - "seo"
                - "some other type"
    assets:
        notes_events:
            types:
                - ""
                - "content"
                - "licese renewal"
                - "some other type"
    objects:
        notes_events:
            types:
                - ""
                - "manual data change"
                - "some other type"
```
