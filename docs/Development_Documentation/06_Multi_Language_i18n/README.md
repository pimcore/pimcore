# Multi Language i18n & Localization in Pimcore

Localization is a very important aspect in every content management. In Pimcore localization can be centrally configured 
and has influence on multiple aspects within the system. 

Bottom line is that using Pimcore in a multi language mode is pretty easy for users and developers. Pimcore takes care 
of all technical aspects and by doing so follows the ZF patterns.

In Pimcore there is a difference between content localization and localization of Pimcore backend. 
 
## Content Localization 

### Language Configuration
The available languages for content are configured centrally in system settings (*Settings* > *System Settings*
> *Localization & Internationalization (i18n/l10n)*). 

![Localization Settings](../img/localization-settings.png)

Following settings can be defined here: 
* Available languages
* System wide default language
* Fallback language for each language: if defined, Pimcore returns values from fallback language if primary language has 
 no values set. 


### Localized Content Areas
The activated languages have influence to following modules of content within Pimcore: 

* [Document - Localization (system property for language)](./02_Localize_your_Documents.md)
* [Shared Translations (Zend_Translate)](./04_Shared_Translations.md)
* [Localized Fields for Objects (object localization)](../05_Objects/01_Object_Classes/01_Data_Types/23_Localized_Fields.md)
* [Structured Data Fields - Classification Store](../05_Objects/01_Object_Classes/01_Data_Types/13_Classification_Store.md)


### Dealing with Locales within our Code
Pimcore offers localization for documents as described above. If you don't want to use that, you can set the locale 
manually in your controller/action: 

```php
$this->setLocale("de_AT");
```

Alternatively you can use the following (does not cover all functionalities - only ZF specific)
```php
$locale = new \Zend_Locale("en_US");
\Zend_Registry::set("Zend_Locale",$locale);
```
Now every Pimcore and ZF module will respect your registered locale.


## Pimcore Backend UI Localization 

Pimcore backend UI localization is independent from content localization but works similar to Shared Translations. 
Basically every text in Pimcore backend is translatable, but there are two different sources for translations - Pimcore
system translations and project specific translations. 


### Pimcore System Translations
This covers all labels and texts within Pimcore that ship with Pimcore installation package. Here the standard language 
English is maintained by the core team. In addition to that, every body can join the 
 [Pimcore translation project](http://www.pimcore.org/en/community/translations) to add system translations in additional
 languages. With every Pimcore release, newly added translations are added to the Pimcore installation package.


### Project Specific Translations
There are several components in the Pimcore backend UI which are configured differently for each project. These are

* object class names
* object field labels
* object layout components
* document types
* predefined properties
* custom views
* document editables

All these elements (except document editables) can be translated in *Extras* > *Translations Admin* similar to the
Shared Translations. All installed system languages are available for translation.

Strings which are subject to special translations, but have not been translated yet, are displayed with a "+" in front 
and after the string, if Pimcore is in DEBUG mode.
 

Document editables are translated through a special view helper.

Example: Translation of options of a select editable
```php

(view script)
...
 
<?= $this->select("select", [
    "store" => [
        ["option1", $this->translateAdmin("Option One")],
        ["option2", $this->translateAdmin("Option Two")],
        ["option3", $this->translateAdmin("Option Three")]
    ]
]); ?>


// short hands

<?= $this->select("select", [
    "store" => [
        ["option1", $this->ts("Option One")],
        ["option2", $this->ts("Option Two")],
        ["option3", $this->ts("Option Three")]
    ]
]); ?>


```
After adding a new translation, the document needs to be loaded once in editmode. This adds the new translation keys to 
to the Admin Translations where all extra translations can be edited.

  
### Plugin Translations
If you are a plugin developer, you can add translations to your plugins and provide them with your plugin as you wish. 
To see how plugins can hook into translations, please see the Plugin Developer's Guide.

