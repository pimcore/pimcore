# Layouts:

Layouts in Pimcore you should put to the ```/website/views/layouts``` directory.
Enable layout in your controller action:

```php
$this->enableLayout();
```

If you wanted to change default layout, you would add line presented below to your template file:

```php
<?php 
//change layout to catalog.php
$this->layout()->setLayout('catalog'); 
?>

<div id="product">

...

```