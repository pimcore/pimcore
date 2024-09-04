# Static Helpers

Pimcore offers some static helpers:

## Pimcore Tool
The `Pimcore\Tool` class is a collection of general service methods. Their names should be self-explaining, just have a look at the [class source file](https://github.com/pimcore/pimcore/blob/11.x/lib/Tool.php).

Particular useful can be following methods:
* `isValidPath()`
* `getValidLanguages()`
* `getHostname()`
* `getHostUrl()`
* `classExists()`
* `getMail()`

### E-Mail
There is a convenience function which allows any Pimcore system component or plugin to use a 
preconfigured `Symfony\Component\Mime\Email` instance based on the Pimcore system settings' email configuration.

```php
$mail = Pimcore\Tool::getMail($recipients, $subject);
// For any plugin or website applications it might be convenient to use this mail configuration instead of having to care for these settings themselves.
```


## Element Service
The `Pimcore\Model\Element\Service` class is a collection of service methods for elements (documents, assets, objects). 
Their names should be self-explaining, just have a look at the [class source file](https://github.com/pimcore/pimcore/blob/11.x/models/Element/Service.php). 

Particular useful can be following methods:
* `getElementByPath()`
* `getSafeCopyName()`
* `pathExists()`
* `getElementById()`
* `getElementType()`
* `createFolderByPath()`
* `getValidKey()`


Also have a look at the sub classes `Pimcore\Model\Asset\Service`, `Pimcore\Model\Document\Service` and 
`Pimcore\Model\DataObject\Service`. 


### Document-Service
A useful service method for documents is `Pimcore\Model\Document\Service::render()`. 

You can use this helper to render a page outside of a view, for example to send mails. 

##### Example:
```php
$optionalParams = ['foo' => 'bar', 'hum'=>'bug'];
$useLayout = true;
$content = Document\Service::render(Document::getById(2), $optionalParams, $useLayout);
echo $content;
```
