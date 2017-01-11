# Pimcore Templates

## Introduction

In MVC the view part defines what is going to be presented to the user. 
In Pimcore templates represent the view part. 

In general the templates are located in: `/website/views/scripts/[controller]/[action].php` 
(both controller as well as action without their suffix).  

Pimcore uses `\Zend_View` as the templating engine. 
Therefore the standard template language is plain PHP. 
But other template languages could be used so Pimcore does not restrict the output technology. 
The documentation uses PHP as template language though. 

## Pimcore Specialities and Examples

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
            <?php 
                echo date("Y-m-d H:i:s");
            ?>
        </div>
    </div>
</div>

</body>
</html>
```

#### Pimcore Specialities
Pimcore provides a few special functionalities to make templates even more powerful. 
These are explained in following sub chapters:
* [Layouts](./00_Layouts.md) - Use layouts to define everything that repeats on a page. 
* [View Helpers](./02_View_Helpers.md) - Use view helpers for things like includes, translations, cache, glossary, etc.
* [Thumbnails](./04_Thumbnails.md) - Learn how to include images into templates with using Thumbnails. 

