# Pimcore Templates

## Introduction

In MVC the view part defines what is presented to the user. In Pimcore templates represent the view part. 

In general the templates are located in: `/website/views/scripts/[controller]/[action].php` 
(both controller as well as action without their suffix).  

As template engine Pimcore uses `\Zend_View`. Therefore the standard template language is plain PHP. But other
 template languages could be used so Pimcore does not restrict the output technology. The documentation uses PHP as 
 template language though. 

## Pimcore specialities and examples

A simple example of a view looks like the following. Lust use HTML/CSS and enrich it with php code. 
But keep in mind, it is bad practise to include busnisess logic code in your view. So keep your view as clean as 
possible and use php just printing out data. 

#### Example

```php 

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
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

#### Pimcore specialities
Pimcore provides a few specialities to make templates even more prowerful. These are explained in following sub chapters:
* [Layouts](./00_Layouts.md) - Use layouts to define everything that repeats on a page. 
* [View Helpers](./02_View_Helpers.md) - Use view helpers for things like includes, translations, cache, glossary, etc.
* [Thumbnails](./04_Thumbnails.md) - Learn how to include images into templates with using Thumbnails. 

