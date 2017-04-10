# Static Helpers

Pimcore offers some static helpers:

## Pimcore Tool
The `Pimcore\Tool` class is a collection of general service methods. Their names should be self 
explaining, just have a look at the [class source file](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Tool.php).

Particular useful can be following methods:
* `isValidPath()`
* `getValidLanguages()`
* `getHostname()`
* `getHostUrl()`
* `getHttpClient()`
* `classExists()`
* `exitWithError()`
* `getMail()`

### E-Mail
There is a convenience function which allows any Pimcore system component or plugin to use a 
preconfigured `Zend_Mail` instance based on the Pimcore system settings' email configuration.

```php
//returns Zend_Mail
$mail = Pimcore\Tool::getMail($recipients,$subject);
For any plugin or website applications it might be convenient to use this mail configuration instead of having to care for these settings themselves.
```


## Element Service
The `Pimcore\Model\Element\Service` class is a collection of service methods for elements (documents, assets, objects). 
Their names should be self explaining, just have a look at the [class source file](https://github.com/pimcore/pimcore/blob/master/pimcore/models/Element/Service.php). 

Particular useful can be following methods:
* `getElementByPath()`
* `getSaveCopyName()`
* `pathExists()`
* `getElementById()`
* `getElementType()`
* `createFolderByPath()`
* `getValidKey()`


Also have a look at the sub classes `Pimcore\Model\Asset\Service`, `Pimcore\Model\Document\Service` and 
`Pimcore\Model\Object\Service`. 


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

#### Advanced:
If you are using this method in a batch job then you may want to clear parameters from the view upon, 
for example:

```php
foreach($users as $user) {
    $myOptions = ['recentlyViewedItems' => $user->getRecentViews()];
    $content = Document\Service::render(Document::getById(2), $myOptions, true);
 
    //clear only the $myOptions out of the view
    $viewHelper = \Zend_Controller_Action_HelperBroker::getExistingHelper("ViewRenderer");
    if($viewHelper && $viewHelper->view !== null) {
        foreach ($myOptions as $key => $value) {
            if ($viewHelper->view->$key) unset($viewHelper->view->$key);
        }
    }
 
    //dosomethingwithContent i.e. mail it
}
```

## Locking
Pimcore provides a simple tool for locking. With that tool it is possible to avoid concurrent
execution of same code sections or functions.

Just have a look at `Pimcore\Model\Lock` and the static class functions 
* `acquire()`
* `release()`
* `lock()`
* `isLocked()`

Active locks are stores in the database table `locks`. 
