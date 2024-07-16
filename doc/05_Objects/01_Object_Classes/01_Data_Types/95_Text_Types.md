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

If a string appears to already be hashed, either by detection by [password_get_info()](https://www.php.net/manual/en/function.password-get-info.php) or if it matches any common hash patterns below, then it will not be hashed again.

 - Hexadecimal string
 - with legth of 32, 40, 48, 56, 64, 96, or 128 characters

These rules will detect the following hashes:
 - MD2, MD4, MD5, RIPEMD-128, Snefru 128, Tiger/128, HAVAL128
 - SHA-1, HAS-160, RIPEMD-160, Tiger/160, HAVAL160
 - Tiger/192, HAVAL192
 - SHA-224, HAVAL224
 - SHA-256, BLAKE-256, GOST, GOST CryptoPro, HAVAL256, RIPEMD-256, Snefru 256
 - SHA-384
 - SHA-512, BLAKE-512, SWIFFT


We recommend using `password_hash` as algorithm, any other algorithm is deprecated since Pimcore 11.2 and will be removed in Pimcore 12.
 

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


## Input Quantity Value

Quite similar to [Quantity Value](55_Number_Types.md) except that text values are allowed instead of the strict restriction to numeric values.
