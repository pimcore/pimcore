## Introduction
Pimcore has three core modules. 
Documents, Assets and Object give you powerful and complete system for creating every kind of a software.

| Module                        | Description                                                                                                                                                                                                                                                                                      |
|-------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Documents](!Documents)       | Module responsible for content management on whole application. Great drag & drop mechanism allows creating cms pages, email templates, offline print materials with live preview. As a developer, you can specify areas possible to edit by end administration panel user.                      |
| [Assets](!Assets)             | Images, videos, documents and every other file. Due to integration with Documents and Objects, you can use these files in content or in data structures for example as a picture of Author object. Available additional features like editing images and other useful operations at files.       |
| [Objects (Classes)](!Objects) | This can be used for RAD (Rapid Application Development). You can easily create a data model with drag & drop, the system creates the model for you in the backend (yes also the php files to work with).                                                                                        |


In this part, you are going to known required minimum of knowledge, crucial for start developing with Pimcore. 
More advanced cases you'll find at links above (in the table).

Table of contents:

[TOC]

## Create template, layout and controller

### New Controller
First of all, we need our own controller. 
Let's call it ContentController.php. 
You have to push the file into ```website/controllers``` directory.

```php
<?php
use Website\Controller\Action;
use Pimcore\Model\Asset;

/**
 * Class ContentController
 */
class ContentController extends Action
{

    public function defaultAction()
    {
        $this->enableLayout();
    }
}
```

There is the only one action *defaultAction*.
The method *enableLayout* registers \Zend_Layout to decorate our content page. 
In the defaultAction, we can put some custom code or assign values to the template.

### Create Template
Now we can create the templates for our new content page:
* Create a new folder in ```/website/view/scripts``` and name it like the controller (in this case “content”). 
* Put a new PHP file into this folder and name it like our action (default.php).

Then we can put some template code into it, for example:

```php
<?php /** @var $this \Pimcore\View */ ?>

<?php $this->layout()->setLayout('default'); ?>

    <h1><?= $this->input("headline", ["width" => 540]); ?></h1>

<?php while ($this->block("contentblock")->loop()) { ?>
    <h2><?= $this->input("subline"); ?></h2>
    <?= $this->wysiwyg("content"); ?>
<?php } ?>
```

### Add Layout
Pimcore uses the advantages of Zend_Layout out of the ZF, for details please read more here about it.
Because we have enabled the layout engine in our controller, we can use layouts to wrap our content page with another template which contains the main navigation, a sidebar, …
With this code:

```php
<?php $this->layout()->setLayout('default'); ?>
```
We tell the engine that we want to use the layout standard. Now create a new php file in the folder /website/views/layouts and name it to default.php (just like the name of the layout appending .php).
Then we can also put some HTML and template code into it:

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
        <div id="logo">
            <a href="http://www.pimcore.org/"><img src="/pimcore/static6/img/logo-gray.svg" style="width: 200px;" /></a>
            <hr />
            <div class="claim">
                THE OPEN-SOURCE ENTERPRISE PLATFORM FOR PIM, CMS, DAM & COMMERCE
            </div>
        </div>
        <div class="info">
            <?php echo $this->layout()->content; ?>
        </div>
    </div>
</body>
</html>
```

The code ```<?= $this->layout()->content ?>``` is the placeholder where your contentpage will be inserted.

### Using a controller in CMS Page
Now we need to connect the action to a page. This will be done in the Pimcore backend.
First, click on the left under "Documents" at "home".

![Create page](/img/Pimcore_Elements_check_homepage.png)

Now select the tab "settings" in the newly opened window.
In controller and action input you have to select the name of the controller and the name of the action.  
If everything goes properly, you see the view like below:

![Page settings](/img/Pimcore_Elements_homepage_settings.png)

You can test the new controller and action, after save.
Just change tab to *edit*. Now, you see your page with an editable place.

![Page edit preview](/img/Pimcore_Elements_homepage_edit_tab.png)

## Introduction to objects
Create simple products database. 
We've already made our own controller, template and view and we're allowed to adding text from admin panel to our pages.

### Update controller and template

Let's create another action in the controller (ContentController) called productAction.
 
```php
<?php
use Website\Controller\Action;
use Pimcore\Model\Asset;

/**
 * Class ContentController
 */
class ContentController extends Action
{
    public function defaultAction ()
    {
        $this->enableLayout();
    }
    
    public function productAction()
    {
        $this->enableLayout();
    }
    
}
```

Then we also need the new template ```website/views/scripts/content/product.php``` 

```php
<?php /** @var $this \Pimcore\View */ ?>
<?php $this->layout()->setLayout('default'); ?>



<h1><?= $this->input("headline", ["width" => 540]); ?></h1>

<div class="product-info">
    <?php if($this->editmode):
        echo $this->href('product');
    else: ?>

<!--            Product information-->

    <?php endif; ?>
</div>

```

New lines:

```php
$this->editmode
```

The parameter above check if you're in the administration area. 

```php
$this->href('product');
```

Href is one of editable elements. It would be used to make relation 1 to 1.
The full list of editables is presented in the special section: [Editables](!Documents/Template/Editables)

### Create the class

Ok, let's create our first class for objects. 



<div class="inline-imgs">

[comment]: #TODOinlineimgs

Go to: ![Settings](/img/Pimcore_Elements_settings.png) **Settings -> Object -> Classes** and click the button with *Add class* label.

</div>

![Add product class](/img/Pimcore_Elements_class_add.png)

Now, there is a new product class.
Classes are like database scheme for the objects. 

More specific knowledge you can find in [Objects section](!Objects)

The product should have: SKU, picture, name and description. 

* Go to the edit page of the class product 
* Click on the right after **Base -> Add layout Component -> Panel** - the main panel for elements of the class created, now we can add main products elements.
* To add elements:
    * Click on the right after **Panel** and then **Add data component -> Text -> Input**, after it change the name of the input to **sku**
    * In the same way, add new component for **product name** and change the name of the input to **name**
    * Now we're going to add WYSIWYG attribute. We use it to create a description for products. **Add data component -> Text -> WYSIWYG**. Let's call it **description**.
    * The last attribute is the picture. We can use special data component from **Other** section called **Image**. Name the attribute **picture**.

If everything goes well, the new class looks like in the picture:

![Product class](/img/Pimcore_Elements_product_class.png)

**Important:** Every generated class in Pimcore admin panel has also mapper in the code. You can find the product class in ```website/var/classes/Object/Product.php``` 

### Add new object

Ok, we've just prepared the simple class for new products. 
Now we can use it to create objects. 

* Open the objects section on the left and click on the right button after **Home** (Note that you can create also directories structure for objects).
* Choose **Add object -> product** and fill the input with some name, for example: **tshirt**
* Add values for sku, name and description attributes.
* Click  *Save & Publish*

Probably, your view looks like below:

![New product](/img/Pimcore_Elements_new_product.png)

## Introduction to Assets

In assets label, you can also create directories structure. 
You can upload there every file like image, movie, document, ... and after, use those in objects and documents.
Add one file which later you will use in an object. 

There are many ways to upload files:
* Just drag it from files browser on your computer
* Right click on the home and choose the most interesting method.

The last step to finish the product object is add a photo.

[comment]: #TODOinlineimgs

<div class="inline-imgs">

The one way to upload a photo is this button: ![Upload image to an object](/img/Pimcore_Elements_upload_button.png) or just drag file which you uploaded from Assets section.

</div>

Click **Save & Publish** button. 

That's it. 

![Complete object](/img/Pimcore_Elements_complete_object.png)

## Add the product object to a document

The last thing is to show the product in the body of the document you created. 

Let's go back to the documents section. Right click on the Home then **Add Page -> Empty Page**.
In the settings label, choose the product action and the content controller, click save and go back to the edit tab.

There is new element (**Href**) which you added in the product template.
Drag the product object to that input and click **Save & Publish**.

![Drag the object to the document](/img/Pimcore_Elements_drag_to_document.png)

Let's see what happened on the front... 

Go to the product page. In my case, it would be *http://pimcore.local/tshirt* where *tshirt* is the identifier of the product (the name visible the documents tree).

We haven't implemented frontend feature yet. Therefore, the page doesn't contain product information.

In the template file (```website/views/scripts/content/product.php```) add few lines:

```php
<?php /** @var $this \Pimcore\View */ ?>
<?php $this->layout()->setLayout('default'); ?>



<h1><?= $this->input("headline", ["width" => 540]); ?></h1>

<div class="product-info">
    <?php if($this->editmode):
        echo $this->href('product');
    else: ?>

    <div id="product">
        <?php
        /** @var \Pimcore\Model\Object\Product $product */
        $product = $this->href('product')->getElement();
        ?>
        <h2><?php echo $this->escape($product->getName()); ?></h2>
        <div class="content">
            <?php echo $product->getDescription(); ?>
        </div>
    </div>

    <?php endif; ?>
</div>

```

You are able to access to your object by method **getElement**.
Now you have access to whole data from the object (name, description, ...).
It's a good practice to add ```@var``` doc in every view. If you do this you have access to autocomplete in IDE.

And the last step, we would like to show the product picture.

```php
<div class="content">
    <?php
    $picture = $product->getPicture();
    if($picture instanceof \Pimcore\Model\Asset\Image):
        /** @var \Pimcore\Model\Asset\Image $picture */
        
    ?>
        <img src="<?php echo (string) $picture; ?>"
             alt="<?php echo $this->escape($product->getName()); ?>">
        
    <?php endif; ?>
    <?php echo $product->getDescription(); ?>
</div>
```

As you see, image attribute is an additional class with usefull parameter.

Now the product page looks like that:

![Final product page](/img/Pimcore_Elements_final_product_page.png)


[Create Pimcore extension](!Start/Create_Extension)