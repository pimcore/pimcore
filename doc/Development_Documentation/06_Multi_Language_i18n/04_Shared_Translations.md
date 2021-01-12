# Shared Translations 

Pimcore provides an easy way for editors to edit commonly used translation terms across the application, which can be found 
here:  `Extras` > `Translation` > `Shared Translations`.
In the background the standard Symfony Translator component is used to add the shared translation functionality to the application. 
The main benefit is that you have only one single translator for all your translations. 

It automatically uses the locale specified on a document or from a fallback mechanism. 

For more information, please also check out [Symfony's Translations Component](http://symfony.com/doc/current/translation.html). 

![Shared Translations](../img/localization-translations.png)

Available languages are defined within the system languages, see [here](./README.md).

## Translations case sensitivity

Translations are case sensitive by default. You can
reconfigure Pimcore to handle website and admin translations as case insensitive, however as this implies a performance
hit (translations might be looked up twice) and it does not  conform with Symfony's translators you're encouraged to reference
translation keys with the same casing as they were saved.

## Working with Shared Translations / the Translator in Code
  
#### Example in Templates / Views

You can also use variable interpolation in localized messages.

```twig
<div>
    <address>&copy; {{ 'Copyright'|trans }}</address>
    <a href="/imprint">{{ 'Imprint'|trans }}</a>
    {# variable interpolation, 'about' translates to 'About {{siteName}}' #}
    <a href="/about">{{ 'about'|trans({'{{siteName}}': siteName}) }}</a>
</div>
```

#### Example in a Controller
 
```php
<?php

namespace AppBundle\Controller;

use Symfony\Contracts\Translation\TranslatorInterface;
use Pimcore\Controller\FrontendController;

class ContentController extends FrontendController
{
    public function defaultAction(TranslatorInterface $translator)
    {
        $translatedLegalNotice = $translator->trans("legal_notice");
        $siteName = "Demo"; // or get dynamically
        // variable interpolation, 'about' translates to 'About {{siteName}}'
        $translatedAbout = $translator->trans("about", ['siteName' => $siteName]);
    }
}
```


## Pimcore backend functionalities

### Sorting & Filtering on language level

![Sorting Shared Translations](../img/localization-translations-sorting.jpg)


### Translation Export & Import

Translations can be exported to a CSV file and then re-imported later on.

![Translation Export](../img/localization-translations-export.jpg)

Translations are still imported automatically as long as the translation key does not exist in the target system or the 
translation itself is still empty. Conflicts (i.e. the translation in the target system does not match the version of 
the source) are shown in an overview tab and then can be merged manually.

![Translation Import](../img/localization-translations-import.jpg)
