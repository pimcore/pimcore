---
title: Pimcore Controller
description: Writing controllers for Pimcore documents using the FrontendController base class.
---

# Pimcore Controller

## Introduction

Pimcore controllers extend `Pimcore\Controller\FrontendController`, which adds document-aware dispatching,
edit mode support, and multi-language features on top of Symfony's standard controller.
Keep controllers lean and delegate business logic to services.

## Conventions and Examples

| Controller Name | File Name                              | Class Name                         | Default View Directory |
|-----------------|----------------------------------------|------------------------------------|------------------------|
| Content         | `src/Controller/ContentController.php` | `App\Controller\ContentController` | `/templates/content`   |
| News            | `src/Controller/NewsController.php`    | `App\Controller\NewsController`    | `/templates/news`      |

In controllers, for every action there exists a separate method ending with the `Action` suffix. 
The `DefaultController` comes with Pimcore. When you create an empty page in Pimcore it will call 
the `defaultAction` in the `DefaultController` which uses the view `/templates/default/default.html.twig`. 

You can render templates the [standard Symfony way](https://symfony.com/doc/current/templates.html#rendering-a-template-in-controllers), either using `$this->render('foo.html.twig')` or the `#[Template]` attribute.


### Examples

```php
<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pimcore\Controller\Attribute\ResponseHeader;

class DefaultController extends FrontendController
{   
    /**
    * Very simple example using $this->render() and passing the parameter 'foo'
    */
    public function myAction(): Response
    {
        return $this->render('content/default.html.twig', ["foo" => "bar"]);
    }

    /**
     * Example using the #[Template] attribute to resolve the view. 
     * The frontend controller also provides methods to add response headers or via attributes without having
     * access to the final response object (as it is automatically created when rendering the view).
     *
     */
     #[Template('/default/header.html.twig')]
     #[ResponseHeader(key: "X-Foo", values: ["123456", "98765"])]
    public function headerAction(Request $request): array
    {
        // schedule a response header via code
        $this->addResponseHeader('X-Foo', 'bar', false, $request);
        
        return ["foo" => "bar"];
    }
    
    /**
     * This action returns a JSON response. 
    */
    public function jsonAction(Request $request): JsonResponse
    {
        return $this->json(array('key' => 'value'));
    }
    
    /**
     * This returns a standard symfony Response object 
    */
    public function customAction(Request $request): Response
    {
        return new Response("Just some text");
    }
}
``` 

### Available Properties

| Name              | Type        | Description                                              |
|-------------------|-------------|----------------------------------------------------------|
| `$this->document` | Document    | Reference to the current document, if any is available.  |
| `$this->editmode` | boolean     | True if the page is rendered in Pimcore Studio edit mode. |
