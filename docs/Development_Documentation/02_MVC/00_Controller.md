# Pimcore Controller

## Introduction

Pimcore controller play the designated role in the MVC pattern. They bind the design patterns together and contain or delegate 
the functionality of the web application. It is good practise to keep the controllers as lean as possible and capsualte
the business logic into models or services/libraries. 

Pimcore offers an abstract class (`Pimcore\Controller\Action\Frontend`), which must be implemented by your controllers 
(or better use `Website\Controller\Action` out of your website folder). This abstract class adds some Pimcore specific 
 dispatching features - especially in combination with Pimcore Documents, multilanguage Support etc. 

The naming of the file and the class is the same as in Zend Framework. 
Because `website` is configured as the default module in the front controller, you don't have to add a prefix to your 
controller class names.

## Pimcore Specialities and Examples

| Controller Name | File Name                   | Class Name        | Default View Directory               |
|-----------------|-----------------------------|-------------------|--------------------------------------|
| content         | `ContentController.php` | ContentController | `/website/views/scripts/content` |
| news            | `NewsController.php`    | NewsController    | `/website/views/scripts/news`    |

In controllers, for every action there exists a separate method ending with the `Action` suffix. 
The `DefaultController` comes with Pimcore. When you create an empty page in Pimcore it will call 
the `defaultAction` in the `DefaultController` which uses the view `/website/views/scripts/default/default.php`. 

Views are tied to actions implicitly using the filename. 
You can override this by using `$this->renderScript('directory/viewname.php')`
 like in the example below.

```php
use Website\Controller\Action;
use Pimcore\Model\Document;
 
class DefaultController extends Action {
 
    public function init() {
        parent::init();
        //Add a layout to all actions in this controller using the default layout at website/views/layouts/layout.php
        $this->enableLayout();
    }
     
    /**
     * Home page action. Will use the template /website/views/scripts/default/home.php as view.
    */
    public function homeAction() {
        //Set a view variable with the name "bodyClass" with the value "home"
        $this->view->bodyClass = 'home';
        //Add a <link> tag to the <head> element in the layout.
        $this->view->headLink()->appendStylesheet('/css/home.css');
        //Add a JavaScript tag to the <head> element in the layout.
        $this->view->headScript()->appendFile('/js/home.js');
    }
     
    /**
    * Default page action. Will use the template /website/views/scripts/default/default.php as view.
    */
    public function defaultAction() {
        //it is perfectly fine for an action to be empty.
    }
    
    /**
    * Example using a different template than the name of the action.
    * Will use the template /website/views/scripts/default/somethingelse.php as view.
    */
    public function differentAction() {
        $this->view->bodyClass = 'different';
        $this->renderScript('default/somethingelse.php');
    }
    
    /**
     * This action returns a JSON response. 
    */
    public function jsonAction() {
        $this->_helper->json(array('key' => 'value'));
    }
```

Put your controllers in the following directory: `/website/controllers`

There are some helpers defined in `Pimcore\Controller\Action\Frontend` (the abstract class your controller must extend). 
But the best way is to use `Website\Controller\Action` (`/website/lib/Website/Controller/Action.php`) which is already shipped with Pimcore 
and implements the `Pimcore\Controller\Action\Frontend` and can be modified and extended the way you need it.

###### Methods Available

| Method                | Arguments                                    | Description                                                                                                                                              |
|-----------------------|----------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `enableLayout`          | none                                   | Register Zend_Layout for you, if called you can use this layout in your views.                                                                           |
| `disableLayout`         | none                                   | The opposite of enableLayout.                                                                                                                            |
| `disableViewAutoRender` | none                                   | Disables the auto renderer for the current action, for actions which don't need a view.                                                                  |
| `removeViewRenderer`    | none                                   | Removes the view renderer. This is not only for the current action, once the renderer is removed you can't render a view anymore, no matter which scope. |
| `forceRender`           | none                                   | Call this method to force rendering, this can be useful when you need the response directly.                                                             |
| `setDocument`           | `Pimcore\Model\Document $document` | With this method you can set the default document for the current action which will be available in the view and the action with `$this->document.`  |

If you want to use one of the methods (hooks) below which are offered by ZF you have to call their parent:

* `preDispatch`
* `postDispatch`
* `init`

###### There are also some properties which can be useful:

| Name                  | Type     | Description                                              |
|-----------------------|----------|----------------------------------------------------------|
| `$this->document` | Document | Reference to the current document, if any is available.  |
| `$this->editmode` | boolean  | True if you are in editmode (admin)                      |
   
  
Example:

```php
use Pimcore\Model\Document;
 ...
 
public function init() {
    parent::init();
     
    // example of properties
    if ($this->document instanceof Document\Page) {
           // do something
    }
    ...
    // your custom code
    ...
}
 
public function preDispatch() {
    parent::preDispatch();
     
    if ($this->editmode) {
           // do something only in editmode
    }
    ...
    // your custom code
    ...
}
 
...
```
