# Pimcore Templates

## Introduction

In general the templates are located in: `/app/Resources/views/[Controller]/[action].html.(php|twig)` 
(both controller as well as action without their suffix).  

Pimcore uses an enhanced version of [Symfony's PHP templating engine](http://symfony.com/doc/current/templating/PHP.html).
For historical reasons, Pimcore defaults to PHP as template language, but can easily reconfigured to use [Twig](./00_Twig.md)
for templating.

We're stil working on adding Twig references throughout the documentation. In the meantime please:

* Have a look at the dedicated [Twig](./00_Twig.md) documentation page
* Check the [Twig Demo](https://github.com/pimcore/pimcore/tree/master/install-profiles/demo-basic-twig) as starting point
* Provide PRs with Twig examples :) See [#1699](https://github.com/pimcore/pimcore/issues/1699#issuecomment-328115727) for
  an example how to add a tabbed PHP/Twig code block to the documentation.

## PHP Templates
  
We've enhanced the default Symfony PHP engine, by adding the `$this` context and an object oriented access to properties 
and templating helpers (eg. `$this->slots()` instead of `$view["slots"]`), which is basically the same as using 
the `$view` variable with the array based syntax or local variables when using the default Symfony style. 
However the default syntax is still available and ready to use.
We've decided to implement the `$this` context for mainly 2 reasons: easier migration from Pimcore 4 and better IDE support
since with the OOP approach we have the full power of auto-complete and code suggestions. 

> It's recommended to have a look into [Symfony's Templating Component](http://symfony.com/doc/current/templating.html) 
which covers much  more topics and goes a bit deeper into detail. 

### Pimcore Specialities and Examples

A simple example of a view looks like the following code. Just use HTML/CSS and enrich it with custom PHP code. 
But keep in mind, it is bad practise to include business logic code in your view. So keep your view as clean as 
possible and use PHP just printing out data. 

#### Example

```php 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Example</title>
</head>
<body>
<div id="site">
    <div id="logo">
        <a href="http://www.pimcore.org/"><img src="/pimcore/static6/img/logo-gray.svg" style="width: 200px;" /></a>
        <hr />
        <div class="claim">
            THE OPEN-SOURCE ENTERPRISE PLATFORM FOR PIM, CMS, DAM & COMMERCE
        </div>
        <div class="time">
            <?= date("Y-m-d H:i:s");  ?>
        </div>
    </div>
</div>

</body>
</html>
```

####  Pimcore Specialities

Pimcore provides a few special functionalities to make templates even more powerful. 
These are explained in following sub chapters:

* [Template inheritance and Layouts](./01_Layouts.md) - Use layouts and template inheritance to define everything that repeats on a page. 
* [View Helpers](./02_Templating_Helpers/README.md) - Use view helpers for things like includes, translations, cache, glossary, etc.
* [Thumbnails](./04_Thumbnails.md) - Learn how to include images into templates with using Thumbnails.

## Twig Templates

Please see the dedicated [Twig documentation page](./00_Twig.md). 
