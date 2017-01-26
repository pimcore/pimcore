# Text Datatypes

## Input

![Input Field](../../../img/classes-datatypes-text1.jpg)

The input field is a simple text input field. It's data is stored in a VARCHAR column in the database. The display 
width and database column length can be configured in the object class definition.


![Input Configuration](../../../img/classes-datatypes-text2.jpg)


To set the value of an input field, the string value needs to be passed to the setter.

```php
$object->setInput("Some Text");
$object->save();
```


## Password

![Password Field](../../../img/classes-datatypes-text3.jpg)

The password field is basically the same as the input field with hidden input characters. It's column length can not be 
changed, since passwords are always hashed using the selected algorithm.  

If a string shorter than 32 characters is passed to the setter, it is assumed that it is a plain text password, so 
Pimcore creates a Hash of that password and stores it in the database.

If a string with 32 characters is passed to the setter, Pimcore assumes that a hash was given and stores the string 
without further hashing in the database. 
The maximum length of a plain text password is 30 characters.

If `password_hash` is selected as algorithm, Pimcore checks with `password_get_info()` if given string is already 
hashed - and does so if not. 

We recommend using `password_hash` as algorithm.
 

![Password Configuration](../../../img/classes-datatypes-text4.jpg)


## Textarea

![Textarea Field](../../../img/classes-datatypes-text5.jpg)

The textarea is an input widget for unformatted plain text. It is stored in a TEXT column in the database. Setting it's 
value works the same as for the input field. The width and height of the input widget can be configured in the object 
field definition.


## WYSIWYG

The WYSIWYG (What You See Is What You Get) input field is identical with the textarea field except for the fact that 
it's input widget allows formatting of text and can even hold images and links (references to assets and documents). 
If images and documents are used in a WYSIWYG widget, they create a dependency for the current object. To insert an 
image, assets can be dragged to a WYSIWYG widget. In order to create a link, a document needs to be dragged and dropped 
on selected text in the WYSIWYG widget. The text is stored as HTML. 

![WYSIWYG Field](../../../img/classes-datatypes-text6.jpg)

#### Editor - Configuration
It's possible to pass a custom CKEditor config object to the wysiwyg editor. 

```
{
  toolbarGroups : [ { name: 'links' }]
}
```

More examples and config options for the toolbar and toolbarGroups can be found at 
[http://docs.ckeditor.com/#!/guide/dev_toolbar](http://docs.ckeditor.com/#!/guide/dev_toolbar). 

Please refer to the [CKeditor 4.0 Documentation](http://docs.ckeditor.com/).
  
##### Global Configuration
You can add a Global Configuration for all WYSIWYG Editors for all objects by setting ```pimcore.object.tags.wysiwyg.defaultEditorConfig```

For this purpose, you can create a plugin and add the configuration in the new created file `/plugins/MyPlugin/static/js/startup.js` like this:

```
pimcore.object.tags.wysiwyg.defaultEditorConfig = {
    allowedContent: true
};
```


