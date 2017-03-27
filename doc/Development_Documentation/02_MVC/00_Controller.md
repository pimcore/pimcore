# Pimcore Controller

## Introduction

Pimcore controllers play the designated role in the MVC pattern. They bind the design patterns together and contain or delegate 
the functionality of the application. It is good practise to keep the controllers as lean as possible and encapsulate
the business logic into models or services/libraries. 

Pimcore offers an abstract class (`Pimcore\Bundle\PimcoreBundle\Controller\FrontendController`), which can be implemented by your controllers 
(or better use `AppBundle\Controller\AbstractController` out of your website folder). This abstract class adds some Pimcore specific 
 dispatching features - especially in combination with Pimcore Documents, multi-language support etc. 

The naming of the file and the class is just the same as in Symfony. 

## Pimcore Specialities and Examples

| Controller Name | File Name                   | Class Name        | Default View Directory               |
|-----------------|-----------------------------|-------------------|--------------------------------------|
| Content         | `src/AppBundle/Controller/ContentController.php` | `AppBundle\Controller\ContentController` | `/app/Resources/views/Content` |
| News            | `src/AppBundle/Controller/NewsController.php`    | `AppBundle\Controller\NewsController`    | `/app/Resources/views/News`    |

In controllers, for every action there exists a separate method ending with the `Action` suffix. 
The `DefaultController` comes with Pimcore. When you create an empty page in Pimcore it will call 
the `defaultAction` in the `DefaultController` which uses the view `/app/Resources/views/Default/default.html.php`. 

Views are tied to actions implicitly using the filename. 
You can override this by using `return $this->render(":Bar:foo.html.php", ["param" => "value"]);`
 like in the example below or you can of course just return a `Response` object, just the way how Symfony works.

```php
use Pimcore\Model\Document;
 
class DefaultController extends AbstractController {
 
    /**
     * Home page action. Will use the template /app/Resources/views/Default/home.html.php
    */
    public function homeAction(Request $request) {
        //Set a view variable with the name "foo" and the value "bar"
        $this->view->foo = 'bar';
    }
     
    /**
    * Default page action. Will use the template /app/Resources/views/Default/default.html.php
    */
    public function defaultAction(Request $request) {
        //it is perfectly fine for an action to be empty.
    }
    
    /**
    * Example using a different template than the name of the action.
    * Will use the template /app/Resources/views/Default/different.html.php as view.
    */
    public function differentAction() {
        return $this->render(":Default:somethingelse.html.php", ["foo" => "bar"]);
    }
    
    /**
     * This action returns a JSON response. 
    */
    public function jsonAction(Request $request) {
        $this->json(array('key' => 'value'));
    }
    
    /**
     * This returns a standard symfony Response object 
    */
    public function customAction(Request $request) {
        return new Response("Just some text");
    }
```

Put your controllers in the following directory: `/src/AppBundle/Controller`

There are some helpers defined in `\Pimcore\Bundle\PimcoreBundle\Controller\FrontendController`. 
But the best way is to use `\AppBundle\Controller\AbstractController` which is already shipped with Pimcore 
and extends the `FrontendController` and can be modified and extended the way you need it.

###### There are also some properties which can be useful:

| Name              | Type        | Description                                              |
|-------------------|-------------|----------------------------------------------------------|
| `$this->document` | Document    | Reference to the current document, if any is available.  |
| `$this->editmode` | boolean     | True if you are in editmode (admin)                      |
| `$this->view`     | `ViewModel` | Used to assign variables to your view (`$this->view->foo = "bar"`) |
   
 