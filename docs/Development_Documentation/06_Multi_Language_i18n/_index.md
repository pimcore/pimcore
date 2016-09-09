# Multi Language i18n & Localization in Pimcore

Localization is a very important aspect in every content management. In Pimcore localization can be centrally configured 
and has influence on multiple aspects within the system. 

Bottom line is that using Pimcore in a multi language mode is pretty easy for users and developers. Pimcore takes care 
of all technical aspects and by doing so follows the ZF patterns.

In Pimcore there is a difference between content localization and localization of Pimcore backend. 
 
## Content Localization 

### Language Configuration
The available languages for content are configured centrally in system settings (```Settings``` -> ```System Settings``` 
-> ```Localization & Internationalization (i18n/l10n)```). 

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
* Localized Fields for Objects (object localization)
* Structured Data Fields - Classification Store


### Dealing with Locales within our code
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


## Pimcore Backend Localization 


