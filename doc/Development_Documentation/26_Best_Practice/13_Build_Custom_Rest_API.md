# Build Custom REST API endpoint

Pimcore offers an extensive [REST web service](https://pimcore.com/docs/5.x/Development_Documentation/Web_Services/index.html) for many entities of the system, such as assets, documents, objects, class definitions, translations, etc.

A common use case for Pimcore application is integration with external systems which requires custom response from API endpoints.
 
One way to achieve this requirement is to build custom REST controller by extending Pimcore AbstractRest [controller](https://github.com/pimcore/pimcore/blob/master/bundles/AdminBundle/Controller/Rest/AbstractRestController.php) and building custom API endpoints.
```php 
<?php

namespace AppBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\Rest\AbstractRestController;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class CustomRestController extends AbstractRestController
{
    /**
     * @Route("/webservice/get-products")
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
>It is important to use prefix **/webservice** for webservice URLs, for authentication to work properly.

Sometimes it is necessary to serialize complete element for API  response. This can be achieved by [overriding a model](https://pimcore.com/docs/5.x/Development_Documentation/Extending_Pimcore/Overriding_Models.html) which implements the \JsonSerializable interface and implementing JsonSerialize method to return the data you require to be serialized.
    
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