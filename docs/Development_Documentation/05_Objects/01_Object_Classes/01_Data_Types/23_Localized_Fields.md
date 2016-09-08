# Localized Fields

The datatype **Localized Fields** is a container which can be filled with selected datatypes and layouts. 
The advantage of this, is to make it very easy to translate fields to the configured languages.

### Define your localized fields

[comment]: #TODOinlineimgs

<div class="inline-imgs">

If it's not already configured, please specify the valid languages for your website. 
You can do this in ![Settings](../../img/Icon_settings.png)**Settings -> System Settings -> Localization & Internationalization**

</div>


![Add languages](../../img/Objects_LocalizedFields_add_language.png)

First of all, you have to configure your localized fields and layouts, this can be simply done in the class editor.

![Add localized fields to a class](../../img/Objects_LocalizedFields_add_data_component.png)

The container for localized fields is created. Now, you can add data component to that container.

![Add data component to localized fields](../../img/Objects_LocalizedFields_add_inputs_to_lf.png)

Pimcore generated the input widgets for every configured language. 
The result in your object editor will look like below:

![Localized page preview](../../img/Objects_LocalizedFields_page_preview.png)

By default, tabs are used if the number of languages does not exceed 15. 
This limit can be changed via the field settings in the class configurator.

![Change tabs limit in Localized Fields](../../img/Objects_LocalizedFields_change_tabs_limit.png)

### Accessing the data

Accessing the data is simple as below:

```php
// with global registered locale
$object = Object::getById(234);
$object->getInput1(); // will return the en_US data for the field "input1"
 
 
// get specific localized data, regardless which locale is globally registered
$object->getInput1("de") // will return the German value for the field "input1"
```

### Setting data

It works in the similar way as getting the data.

```php
$object = Object::getById(234);
$object->setInput1("My Name", "fr") // set the French value for the field "input1"
```

**Warning:** Please note that moving a field from outside (normal object field) into the localizedfield container means the loss of data from the field in all objects using this class.