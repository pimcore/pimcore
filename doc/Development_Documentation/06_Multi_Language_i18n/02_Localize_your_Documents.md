# Localize your Documents

Pimcore allows you to localize every document. You can find the setting in your document in the tab `Properties`. 
There you can choose from language which is configured in the system settings.

The selected language is registered as a property on the document, which is inherited to all of its children. 

If you have selected a language this will be automatically set on your request object (`$request->getLocale()`) and is 
therefore automatically used for shared translations, localized object lists and all other localized kind of contents. 
 
![Localization Settings](../img/localization-documents.png)

Since the language is a simple property you can access it like every other property like:

```php
 // in your controller / action
 $locale = $request->getLocale(); 
 //or 
 $language = $this->document->getProperty("language");
  
 // any document
 $doc = \Pimcore\Model\Document::getById(234);
 $language = $doc->getProperty("language");
  
 ```
 
 <div class="code-section">
    
 ```php
 $this->getLocale();
 // or 
 $language = $this->document->getProperty("language");
  
 ```
 
 ```twig
 {% set documentLanguage = document.getProperty('language') %}
 ```
 
</div>
 
Once you have defined the language of your documents you can also use the [translate helper](./04_Shared_Translations) 
in your views, as described [here](./04_Shared_Translations). Pimcore uses the standard Symfony translator, 
so you can even access all the translations provided by your bundles. 

## Translating terms on the website
Pimcore comes with a translation module for the website which is explained in [Shared Translations](./04_Shared_Translations.md). 
This is what needs to be done to use translations in templates and display them accordingly on the website (frontend). 
E.g. button names, labels - all the stuff that is predefined by the template and not entered by the editor.

### More about this
To learn more about this visit the [best practices for multilanguage pages](../26_Best_Practice/04_Multilanguage_Setup.md)
