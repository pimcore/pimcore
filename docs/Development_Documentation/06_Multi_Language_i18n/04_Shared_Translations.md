# Shared Translations 

Pimcore provides a simple translation-tool based on ```\Zend_Translate``` - Shared Translations or formerly Website Translations.

It automatically uses the locale specified on a document. If no locale is present, you can still register a locale manually 
in your code using ```\Zend_Registry::set("Zend_Locale", new \Zend_Locale("en"));```

For using the shared translations in frontend, just use the translate helper of ```\Zend_View``` in your templates with
```<?= $this->translate("translation_key") ?>``` or also by using a shorthand ```<?= $this->t("translation_key"); ?>```. 


Once a translation-key is requested Pimcore registers the key in the translation administration, and you can edit all 
the translations in a grid in Pimcore backend at ```Extras``` -> ```Translation``` -> ```Shared Translations```.

![Localization Language Trees](../img/localization-translations.png)


Available languages are defined within the system languages, see [here](./_index.md). 
