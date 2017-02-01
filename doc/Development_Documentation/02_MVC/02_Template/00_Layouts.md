# Pimcore Template Layouts

## Introduction

Layouts define everything that repeats on one page to another, such as a header, footer, navigation. 
Layouts often contain the basic structure of a HTML document, such as `<html>`, `<head>` and the `<body>` tag as well as scripts and stylesheets.

Layouts in Pimcore are located in `/website/views/layouts`.


## Usage of Layouts

###### A Simple Sample Layout Looks Like the Following:  

```php
<!DOCTYPE html>
<html lang="en">
<?php /** @var $this \Pimcore\View */ ?>
<head>
    <meta charset="UTF-8">
    <title>Example</title>
    <link rel="stylesheet" type="text/css" href="/website/static/css/screen.css" />
</head>
<body>
    <div id="site">
        <?= $this->layout()->content; ?>
    </div>
</body>
</html>
```

Of course, PHP can be used within the layout file and therefore layouts can become much more complicated. The most 
important line though is `<?= $this->layout()->content; ?>`. It includes the actual rendered content of the view. 


###### To Enable a Layout in Your Controller Action Use:

```php
    $this->enableLayout();
```

By default, the layout `layout.php` is used. If you want to change default layout, you 
 need to add the following line to specify another layout. You can use as many layouts as you like.

```php
<?php
 
    //change layout to catalog.php
    $this->layout()->setLayout('catalog'); 
?>

<div id="product">
    ...

```

