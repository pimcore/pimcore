# URLs Based on Custom (Static) Routes 

## Introduction
Static routes are necessary for functionalities where you don't have an underlying document or where you have the need
of dynamic URLs. For example you have a news list, which is generated out of a Pimcore object list and you want to give 
the news a detail page. Or you want create product lists with detail pages, or cart pages, a checkout process, ... 

All things where Documents are not practical. Here Custom Routes come into action and allow the definition of URL patterns 
 that are delegated to specific controllers with specific views.
  
Custom Routes come fourth in the route processing priority.

Custom routes are an alternative to Symfony's routing functionalities and give you a bit more flexibility, but you can 
still use [Symfony's routing capabilities](http://symfony.com/doc/current/routing.html) (eg. @Route() annotation,
 `routing.yml`, ...) in parallel to Pimcore Custom Routes.
 
## Configuring Custom Routes

Custom Routes are configured in the Pimcore backend interface as follows. 

![Grid with the new route](../../img/custom-routes.png)

Following options are relevant: 
* *Name* - name of the Custom Route for identifying it
* *Pattern* - URL pattern configured with a regex
* *Reverse* - reverse pattern that is used to build URLs for this route, see also [Building URLs](#building-urls-based-on-custom-routes).
* *Bundle* - When this column is filled, Pimcore routes the request to a different bundle than the standard bundle (AppBundle). 
* *Controller*, *Action* - configuration for which controller/action the request is delegated to. You can use a Service as Controller Name as well. In this case, the Bundle Setting will be ignored
* *Variables* - comma-seperated list of names for the placeholders in the pattern regex. At least all variables used in the reverse pattern must be listed here.  
* *Defaults* - defaults for variables separated by | - e.g. key=value|key2=value2 
* *Site* - Site for which this route should be applied to. 
* *Priority* - priority in resolving the URL pattern. 

Routes are saved in PHP configuration files on the file system (`var/config/staticroutes.php`), so it's also possible to edit them directly in your 
favorite IDE and keep track of the changes in your VCS (eg. Git).

## Accessing Variables in Controller
This is how you can access (form a controller action) the values of the variables (placeholders) you specified in 
the custom route:

![Custom Routes and Variables](../../img/custom-routes2.png)

```php
<?php

namespace AppBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;

class NewsController extends FrontendController
{
    public function detailAction(Request $request)
    {
        $id = $request->get('id');
        $text = $request->get('text');
        
        // ...
    }
}
```

The default variables can be accessed the same way.


## Building URLs based on Custom Routes

URLs are generated using the default URL helper of Symfony `$this->path()` and `$this->url()`. Additionally to the 
standard helpers for generating URLs, Pimcore offers a special templating helper (`$this->pimcoreUrl()`) to generate URLs like you did with Pimcore 4. 
You can define a placeholder in the reverse pattern with %NAME and it is also possible to define an optional part, 
to do so just embrace the part with curly brackets { } (see example below).


| Name          | Pattern                                                  | Reverse                                          | Module     | Controller     | Action     | Variables                | Defaults     | Site     | Priority     |
|---------------|----------------------------------------------------------|--------------------------------------------------|------------|----------------|------------|--------------------------|--------------|----------|--------------|
| news category | /\\/news-category\\/([^_]+)_([0-9]+)(_category_)?([0-9]+)?/ | /news-category/%text_%id{_category_%category_id} |            | news           | list       | text,id,text2,categoryId |              |          | 1            |
  
![Grid with the new route](../../img/Routing_grid2.png)

Due to optional parameters, the above example matches for the following URL's:

* /news-category/testcategory_12_category_2
* /news-category/testcategory_12

#### Generating URL with Optional Parameters

Source url: `/some-other-url`

```php
$this->path('news category', [
    'text' => 'Test',
    'id' => 67,
    'categoryId' => 33,
    'getExample' => 'some value'
]);
```

Since there is no default parameter available out of the route pattern, you have to set every not optional parameter. 
In addition there is one parameter which is not in the reverse route. That will be added as a normal GET parameter in the URL.

Output will be: `/news-category/test_67_category_33?getExample=some+value`


### Adding Default Values to the Route

You can use the *Defaults* column to add default values which will be used if you don't specify parameters in the
 url helper.


| ... | Defaults               | ... |
|-----|------------------------|-----|
| ... | id=5\|text=random text | ... |

![Default values in the route](../../img/Routing_default_values.png)

```php
$this->path("news category", [
    "category_id" => 776
]);
```

Output will be: `/news-category/random+text_5_category_776`


### Setting priorities

There might be cases where you want to use a same pattern at the beginning, but in same time you require a completely different controller, action or additional parameters.
In the example below you can see when exactly you **need** to set the priorities, if you leave those empty, depending on your environment, you may experience an uncommon behavior where one of your pattern will be completely ignored.

In example below you can see how both routes are regulated by priorities.


| ... | Pattern              | Reverse          | ... | Controller | Action | Variables | ... | Priority |
|-----|----------------------|------------------|-----|------------|--------|-----------|-----|----------|
| ... | /\/blog\/(.+)/       | /blog/%month     | ... | blog       | list   | month     | ... | 1        |
| ... | /\/blog\/(.+)\/(.+)/ | /blog/%month/%id | ... | blog       | detail | month,id  | ... | 2        |


### Site Support

It's possible to generate URL's pointing to a different Site inside Pimcore. To do so, set the option *Site*. 
 
#### Example: Linking to the Site with the ID 3

```php

// using the Site object
echo $this->path("news", [
    "id" => 4,
    "text" => "some-text",
    "site" => \Pimcore\Model\Site::getById(3)
]);


// using the ID
echo $this->path([
    "id" => 4,
    "text" => "some-text",
    "site" => 3
], "news");

// using one of the hostname assiged to the site
echo $this->path("news", [
    "id" => 4,
    "text" => "some-text",
    "site" => "subsite.example.com"
]);

```

#### Example: Linking Back to the Main-Site

```php
echo $this->path("news", [
    "id" => 4,
    "text" => "some-text",
    "site" => 0
]);

```

## Dynamic controller / action / module out of the Route Pattern

Pimcore supports dynamic values for the controller, action and the module. 

It works similar to the reverse route, you can place your placeholders directly into the controller.
The following configuration should explain the way how it works:

| Name                          | Pattern                                    | Reverse                    | Module             | Controller             | Action             | Variables             | Defaults             | Site             | Priority             |
|-------------------------------|--------------------------------------------|----------------------------|--------------------|------------------------|--------------------|-----------------------|----------------------|------------------|----------------------|
| articles-dynamic-prefix       | /\\/(events\|news)\\/(list\|detail)/       | /%con/%act                 |                    | %con                   | %act               | con,act               |                      |                  | 10                   |
| articles-dynamic-simple       | /\\/dyn_([a-z]+)\\/([a-z]+)/               | /dyn_%controller/%action   |                    | %controller            | %action            | controller,action     |                      |                  | 1                    |

![Advanced routes grid](../../img/Routing_grid_advanced_routes.png)

In that case, you have few valid URL's:
* `/news/list` - `NewsController::listAction`
* `/events/detail` - `EventsController::detailAction`

## Using Controller as Service in Custom Routes

Pimcore supports Controller as Services in Custom Routes. To add them, set the Controller Setting to your Service name.

Service Definition:

```yml
services:
  app.controller.default:
    class: AppBundle\Controller\DefaultController
    calls:
      - [setContainer, ['@service_container']]

```

It works similar to the reverse route, you can place your placeholders directly into the controller.
The following configuration should explain the way how it works:

| Name          | Pattern     | Reverse  | Bundle | Controller             | Action        | Variables             | Defaults             | Site             | Priority             |
|---------------|-------------|----------|--------|------------------------|---------------|-----------------------|----------------------|------------------|----------------------|
| service_route | /\/default/ | /default |        | app.controller.default | defaultAction |                       |                      |                  | 10                   |


## Responding 404 Status Code

Sometimes you want to trigger a correct 404 error within your controller/action (addressed by a custom route), 
for example when a requested object (in the route) doesn't exist anymore. 

Example:

```php
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException; 

// ...

public function testAction(Request $request) {
    $object = Object::getById($request->get("id")); 
    if( !$object || ( !$object->isPublished() && !$this->editmode) ) {
        return new NotFoundHttpException('Not found');
    }
}
```
