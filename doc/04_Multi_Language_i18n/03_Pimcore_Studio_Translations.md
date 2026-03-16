---
title: Pimcore Studio Translations
description: "Manage Pimcore Studio UI translations, override system translations, and add custom languages."
---

# Pimcore Studio Translations

The entire Pimcore Studio interface is fully translatable. All UI-related translations are managed through
**Translations > Translations** in Pimcore Studio, filtered to the `studio` domain. Pimcore Studio translations use the
same Symfony Translator component as [Shared Translations](./02_Shared_Translations.md), but operate on the `studio`
domain instead of the default `messages` domain. This separation keeps website-facing translations cleanly isolated
from the Studio interface.

## What Can Be Translated

Several components in Pimcore Studio are configured differently for each project and can be translated through the
translations UI. These include:

- Object class names
- Object field labels
- Object layout components
- Document types
- Predefined properties
- Custom views
- Document editables (translated directly in the editable itself, not through the translations UI)

All these elements (except document editables) are translated via **Translations > Translations** using the `studio`
domain. All installed system languages are available for translation. You can also override the system translations
shipped with Pimcore, so you can customize any text within Pimcore Studio.

## System Translations

Pimcore ships a set of built-in translations for the Studio interface. The core team maintains the English base
translations. In addition to that, everybody can join the community translation projects on POEditor to contribute
translations in additional languages:

- [Essential translations](https://poeditor.com/join/project/VWmZyvFVMH) - core UI labels and messages
- [Extended translations](https://poeditor.com/join/project/XliCYYgILb) - additional UI strings

With every Pimcore release, newly added community translations are included in the installation package. Only languages
that have reached a certain translation progress are included in the main distribution.

System translations can be overridden by project-specific translations in the `studio` domain (see below).

## Project-Specific Translations

You can override any system translation or add entirely new translation keys through **Translations > Translations**
in Pimcore Studio. Select the `studio` domain, then add or modify entries as needed. Project-specific translations
take precedence over the built-in system translations.

## Using Studio Translations in Templates

Studio translations use the same `trans` filter as shared translations, but you need to specify the `studio` domain
explicitly. This is useful when you want to display Studio-translated labels in your project templates.

```twig
{{ pimcore_select("select", {
    "store": [
        ["option1", "Option One"|trans({}, 'studio')],
        ["option2", "Option Two"|trans({}, 'studio')],
        ["option3", "Option Three"|trans({}, 'studio')]
    ]
}) }}
```

The first argument to `trans` passes parameters (empty here), and the second argument specifies the translation domain.

## Case Sensitivity

Studio translations follow the same case sensitivity logic as shared translations. See
[Translations Case Sensitivity](./02_Shared_Translations.md#translations-case-sensitivity) for details.

## Adding Custom Languages

Only languages with sufficient translation progress on POEditor are included in the default Pimcore distribution.
If you want to make additional languages available for the Pimcore Studio interface, place a Symfony translation file
for the desired language into the default Symfony translator path. For example, to make Afrikaans available:

```bash
# Create the file at translations/studio.af.yaml
# A comment is sufficient - the file's presence registers the language
echo '# Afrikaans' > translations/studio.af.yaml
```

Unless you have configured a different path, translation files are located at `%kernel.project_dir%/translations`.
