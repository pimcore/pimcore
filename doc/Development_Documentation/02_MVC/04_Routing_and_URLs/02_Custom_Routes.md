# URLs Based on Custom (Static) Routes 

## Introduction
Static routes are necessary for functionalities where you don't have an underlying document or where you have the need
of dynamic URLs. For example you have a news list, which is generated out of a Pimcore object list and you want to give 
the news a detail page. Or you want create product lists with detail pages, or cart pages, a checkout process, ... 

All things where Documents are not practical. Here Custom Routes come into action and allow the definition of URL patterns 
 that are delegated to specific controllers with specific views.
  
Custom Routes come fourth in the route processing priority.
 
## Configuring Custom Routes

Custom Routes are configured in the Pimcore backend interface as follows. 

![Grid with the new route](../../img/custom-routes.png)

Following options are relevant: 
* *Name* - name of the Custom Route for identifying it
* *Pattern* - URL pattern configured with a regex
* *Reverse* - reverse pattern that is used to build URLs for this route, see also [Building URLs](#building-urls-based-on-custom-routes).
* *Module* - When this column is filled, Pimcore routes the request to a different module than the standard module (website module). 
   Enter the name (=folder name) of the Plugin to which you want to route the request  
* *Controller*, *Action* - configuration for which controller/action the request is delegated to. 
* *Variables* - comma-seperated list of names for the placeholders in the pattern regex. 
* *Defaults* - defaults for variables separated by | - e.g. key=value|key2=value2 
* *Site* - Site for which this route should be applied to. 
* *Priority* - priority in resolving the URL pattern. 

Routes are saved in PHP configuration files on the file system (`website/var/config/staticroutes.php`), so it's also possible to edit them directly in your 
favorite IDE and keep track of the changes in your VCS (eg. Git).

## Accessing Variables in Controller
This is how you can access (form a controller action) the values of the variables (placeholders) you specified in 
the custom route:

![Custom Routes and Variables](../../img/custom-routes2.png)

```php
class NewsController extends Action
{
    public function detailAction()
    {
        $id = $this->getParam('id');
        $text = $this->getParam('text');
        
        ...
```

The default variables can be accessed the same way.


## Building URLs based on Custom Routes

URLs are generated using the default `\Zend_View` URL helper `$this->url()`. 
You can define a placeholder in the reverse pattern with %NAME and it is also possible to define an optional part, 
to do so just embrace the part with curly brackets { } (see example below).


| Name          | Pattern                                                  | Reverse                                          | Module     | Controller     | Action     | Variables                | Defaults     | Site     | Priority     |
|---------------|----------------------------------------------------------|--------------------------------------------------|------------|----------------|------------|--------------------------|--------------|----------|--------------|
| news category | /\\/news-category\\/([^_]+)_([0-9]+)(_category_)?([0-9]+)?/ | /news-category/%text_%id{_category_%category_id} |            | news           | list       | text,id,text2,categoryId |              |          | 1            |
  
![Grid with the new route](../../img/Routing_grid2.png)

Due to optional parameters, the above example matches for the following URL's:

* /news-category/testcategory_12_category_2
* /news-category/testcategory_12

#### Generating url with Optional Parameters

Source url: `/some-other-url`

```php
$this->url([
    'text' => 'Test',
    'id' => 67,
    'categoryId' => 33,
    'getExample' => 'some value'
], 'news category');
```

Since there is no default parameter available out of the route pattern, you have to set every not optional parameter. 
In addition there is one parameter which is not in the reverse route. That will be added as a normal GET parameter in the URL.

Output will be: `/news-category/test_67_category_33?getExample=some+value`


### Reusing Existing URL Parameters

Source url: `/some-example/some~random~text_45`
```php
$this->url([
        "categoryId" => 776
    ],
    "news category"
)
```
The parameters `text` and `id` are available via the route pattern, so the will be added automatically if you don't specify them.

Output will be: /some-example/This+is+some+random+text_45_category_776


Source url: `/some-example/some~random~text_45`
```php
$this->url([
        "id" => 776
    ],
    "news category"
)
```
Output will be: /some-example/This+is+some+random+text_776


### Adding Default Values to the Route

You can use the *Defaults* column to add default values which will be used if you don't specify parameters in the
 url helper.


| ... | Defaults               | ... |
|-----|------------------------|-----|
| ... | id=5\|text=random text | ... |

![Default values in the route](../../img/Routing_default_values.png)

```php
$this->url([
    "category_id" => 776
], "news category");
```

Output will be: `/news-category/random+text_5_category_776`


### Site Support

It's possible to generate URL's pointing to a different Site inside Pimcore. To do so, set the option *Site*. 
 
#### Example: Linking to the Site with the ID 3

```php

// using the Site object
echo $this->url([
    "id" => 4,
    "text" => "some-text",
    "site" => \Pimcore\Model\Site::getById(3)
], "news");


// using the ID
echo $this->url([
    "id" => 4,
    "text" => "some-text",
    "site" => 3
], "news");

// using one of the hostname assiged to the site
echo $this->url([
    "id" => 4,
    "text" => "some-text",
    "site" => "subsite.example.com"
], "news");

```

#### Example: Linking Back to the Main-Site

```php
echo $this->url([
    "id" => 4,
    "text" => "some-text",
    "site" => 0
], "news");

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
* `/news/list` - `\NewsController::listAction`
* `/events/detail` - `\EventsController::detailAction`
 

## Using URL helper for query string URL generation

Sometimes it is useful to generate a link with just a query string. 
You can do so by using `false`  as the 2nd parameter (instead of a routes name). 

```php

$this->url(["foo" => "bar"], false);
// ==> /?foo=bar

```

## Responding 404 Status Code

Sometimes you want to trigger a correct 404 error within your controller/action (addressed by a custom route), 
for example when a requested object (in the route) doesn't exist anymore. 

Example:

```php
public function testAction() {
    $object = Object::getById($this->getParam("id")); 
    if( !$object || ( !$object->isPublished() && !$this->editmode && !$this->getParam('pimcore_object_preview') && !$_COOKIE['pimcore_admin_sid'] ) ) {
        throw new \Zend_Controller_Router_Exception("the requested object doesn't exist anymore");
    }
}
```
