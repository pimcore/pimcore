# Template Inheritance and Layouts

## Introduction

Layouts define everything that repeats on one page to another, such as a header, footer, navigation. 
Layouts often contain the basic structure of a HTML document, such as `<html>`, `<head>` and the `<body>` 
tag as well as scripts and stylesheets.

In Symfony/Pimcore, this problem is thought about differently: a template can be decorated by another one. 
This works exactly the same as PHP classes: template inheritance allows you to build a base "layout" 
template that contains all the common elements of your site defined as blocks (think "PHP class with 
base methods"). A child template can extend the base layout and override any of its blocks 
(think "PHP subclass that overrides certain methods of its parent class").

Layout scripts are just normal view scripts and are located together with normal view scripts in: `/app/Resources/views`

For more details about template inheritance and layouts, please have a look at the 
[Symfony documentation](http://symfony.com/doc/current/templating.html#template-inheritance-and-layouts). 

## Usage of Layouts

###### A Simple Sample Layout Looks Like the Following:  

```php
<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */ 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Example</title>
    <link rel="stylesheet" type="text/css" href="/static/css/global.css" />
</head>
<body>
    <div id="site">
        <?php $this->slots()->output('_content') ?>
    </div>
</body>
</html>
```

Of course, PHP, editables and template helpers can be used within the layout file and therefore layouts can become much 
more complicated. The most important line though is `<?php $this->slots()->output('_content') ?>`. 
It includes the actual rendered content of the view. 


###### Use a Layout in a template

Layouts are simply used by declaring a parent template with the following code. 

```php
$this->extend('layout.html.php');
```

In this example we extend from the template `layout.html.php`, but we can use any other and as many as needed 
scripts instead.  
  
A complete example of a document page would look like the following: 


```php
<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('layout.html.php');

?>

<h1><?= $this->input("headline", ["width" => 540]); ?></h1>

<?= $this->wysiwyg("content") ?>
```
