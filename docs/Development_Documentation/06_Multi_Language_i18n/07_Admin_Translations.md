# Admin Translations 

There are several components in the Pimcore backend UI which are configured differently for each project. These are

* object class names
* object field labels
* object layout components
* document types
* predefined properties
* custom views
* document editables

All these elements (except document editables) can be translated in *Extras* > *Translations Admin* similar to the
Shared Translations. All installed system languages are available for translation. It's even possible to override
the system translations shipped with Pimcore, so basically you can translate anything within the backend UI. 

Strings which are subject to special translations, but have not been translated yet, are displayed with a "+" in front 
and after the string, if Pimcore is in DEBUG mode.

But you can use the admin translation also in your custom templates. 
Admin translations use the same translator component (Symfony) but on a different domain.

#### Example: Translate Options of a Select Editable
```php
 <?= $this->select("select", [
     "store" => [
         ["option1", $this->translate("Option One", [], "admin")],
         ["option2", $this->translate("Option Two", [], "admin")],
         ["option3", $this->translate("Option Three", [], "admin")]
     ]
 ]); ?>
 ```
 
