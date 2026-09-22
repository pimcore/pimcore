---
title: Static Helpers
description: Utility classes for elements, documents, and general operations.
---

# Static Helpers

Pimcore offers several static helper classes:

## Pimcore Tool

The `Pimcore\Tool` class is a collection of general service methods.
See the [class source file](https://github.com/pimcore/pimcore/blob/2026.x/lib/Tool.php)
for the complete API.

Particularly useful methods:
* `isValidPath()`
* `getValidLanguages()`
* `getHostname()`
* `getHostUrl()`
* `classExists()`
* `getMail()`

### E-Mail

A convenience function that returns a preconfigured `Symfony\Component\Mime\Email` instance
based on the Pimcore email configuration:

```php
$mail = Pimcore\Tool::getMail($recipients, $subject);
// For any bundle or application, this avoids having to configure email settings independently.
```


## Element Service

The `Pimcore\Model\Element\Service` class is a collection of service methods
for elements (documents, assets, objects).
See the [class source file](https://github.com/pimcore/pimcore/blob/2026.x/models/Element/Service.php)
for the complete API.

Particularly useful methods:
* `getElementByPath()`
* `getSafeCopyName()`
* `pathExists()`
* `getElementById()`
* `getElementType()`
* `createFolderByPath()`
* `getValidKey()`


Also see the sub-classes `Pimcore\Model\Asset\Service`, `Pimcore\Model\Document\Service`,
and `Pimcore\Model\DataObject\Service`.


### Document Service

A useful method for documents is `Pimcore\Model\Document\Service::render()`.

Use this helper to render a page outside of a view, for example to send emails.

##### Example:
```php
$optionalParams = ['foo' => 'bar', 'hum'=>'bug'];
$useLayout = true;
$content = Document\Service::render(Document::getById(2), $optionalParams, $useLayout);
echo $content;
```
