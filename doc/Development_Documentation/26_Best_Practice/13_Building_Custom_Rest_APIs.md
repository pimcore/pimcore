# How to Build a Custom REST API Endpoint

Pimcore offers an extensive [REST web service](../24_Web_Services/README.md) for many entities of the system, 
such as assets, documents, objects, class definitions, translations, etc.

A common use case for applications build with Pimcore is integrating with external systems, 
which requires custom response from API endpoints.
 
One way to achieve this requirement is to build custom controller action that exposes just the right data 
in the desired format. 

### Example

```php 
<?php

namespace AppBundle\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class CustomRestController extends FrontendController
{
    /**
     * @Route("/json-service/get-products")
     */
    public function defaultAction(Request $request)
    {
        $this->checkPermission('objects'); //check if user has permission on objects

        $blogs = new DataObject\BlogArticle\Listing();

        foreach ($blogs as $key => $blog) {
            $data[] = array(
                "title" => $blog->getTitle(),
                "description" => $blog->getText(),
                "tags" => $blog->getTags());
        }

        return $this->adminJson(["success" => true, "data" => $data]);
    }
}

```

Sometimes it is necessary to serialize complete element for API  response. 
This can be achieved by [overriding a model](../20_Extending_Pimcore/03_Overriding_Models.md) 
which implements the `\JsonSerializable` interface and implementing `jsonSerialize` method to return the data you require to be serialized.
    
 ```php
 <?php
 
 namespace AppBundle\Model\DataObject;
 
 class BlogArticle extends \Pimcore\Model\DataObject\BlogArticle implements \JsonSerializable {
 
     public function jsonSerialize()
     {
         $vars = get_object_vars($this);
 
         return $vars;
     }
 }
 ```