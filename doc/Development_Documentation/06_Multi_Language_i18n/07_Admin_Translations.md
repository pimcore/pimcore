# Admin Translations 

There are several components in the Pimcore backend UI which are configured differently for each project. These are

* object class names
* object field labels
* object layout components
* document types
* predefined properties
* custom views
* document editables

All these elements (except document editables) can be translated in *Settings* > *Admin Translations* similar to the
Shared Translations. All installed system languages are available for translation. It's even possible to override
the system translations shipped with Pimcore, so basically you can translate anything within the backend UI. 

Strings which are subject to special translations, but have not been translated yet, are displayed with a "+" in front 
and after the string, if Pimcore is in DEBUG mode.

But you can use the admin translation also in your custom templates. 
Admin translations use the same translator component (Symfony) but on a different domain.

Admin translations underly the same case sensitivity logic as [shared translations](./04_Shared_Translations.md#page_Translations_case_sensitivity).

#### Example: Translate Options of a Select Editable

<div class="code-section">

```php
<?= $this->select("select", [
    "store" => [
        ["option1", $this->translate("Option One", [], "admin")],
        ["option2", $this->translate("Option Two", [], "admin")],
        ["option3", $this->translate("Option Three", [], "admin")]
    ]
]); ?>
```

```twig
{{ pimcore_select("select", {
	"store": [
		["option1", {{ "Option One"|trans({}, 'admin') }}],
		["option2", {{ "Option Two"|trans({}, 'admin') }}],
		["option3", {{ "Option Three"|trans({}, 'admin') }}]
	]
}) }}
```
</div>

#### Adding your own admin languages (since v6.3.6)
Pimcore comes with a set of translations which are managed by [POEditor](https://poeditor.com/join/project/VWmZyvFVMH). 
However, the amount of available languages is limited, because only languages with certain translation progress are
included in the main distribution. 
If you want make additional languages available for the admin interface, you can do so by putting a symfony translation
file for the desired language into the default path for the symfony translator 
(e.g. use `translations/admin.af.yml` for making `Afrikaans` available, the translation file can be also empty). 
If you haven't configured anything different this is `%kernel.project_dir%/translations` for Symfony 4 projects.

#### Override language of admin translations in editmode of documents
In some projects you want to use a different language as admin translations, e.g. English (en) instead of Croatian (hr) or Chinese (zh_Hans) instead of Chinese (zh).

```yaml
# src/AppBundle/Resources/config/pimcore/config.yml
pimcore:
    translations:
        admin_translation_mapping:
            'hr': 'en'
            'zh': 'zh_Hans'
```
