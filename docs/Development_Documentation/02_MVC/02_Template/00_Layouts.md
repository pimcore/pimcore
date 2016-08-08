# Pimcore Template Layouts

## Introduction

Layouts define everything that repeats on one page to another, such as a header, footer, navigation and included styles and scripts. 
Layouts often contain the ```<HTML>``` tag as well as the ```<HEAD>``` and ```<BODY>``` tags.

Layouts in Pimcore are located in the ```/website/views/layouts``` directory.


## Usage of Layouts

###### One simple sample layout looks like the following:  

```php
<?php /** @var $this \Pimcore\View */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Example</title>
    <link rel="stylesheet" type="text/css" href="/website/static/css/screen.css" />
</head>
<body>
    <div id="site">
        <?php echo $this->layout()->content; ?>
    </div>
</body>
</html>
```

Of course, php can be used within the layout file and therefore layouts can become much more complicated. The most 
important line though is ```echo $this->layout()->content;```. It includes the actual template content. 


###### To enable a layout in your controller action use:

```php
    $this->enableLayout();
```

By default, the default layout ```layout.php``` is used. If you wanted to change default layout, you 
 need to add the following lines to your template file:

```php
<?php 
//change layout to catalog.php
$this->layout()->setLayout('catalog'); 
?>

<div id="product">

...

```

