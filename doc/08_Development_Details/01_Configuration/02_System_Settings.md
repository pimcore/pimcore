---
title: System Settings
description: System-wide settings for localization, debugging, documents, objects, and assets.
---

# System Settings

Open system settings in Pimcore Studio via the mega menu (*System* > *System Settings*). Restrict changes
to developers.

Pimcore stores these settings in `var/config/system_settings/system_settings.yaml` or in the settings store,
depending on your configuration. The production environment uses the settings store by default.

To save system settings into the settings store, add the following to your configuration:

```yaml
pimcore:
    config_location:
        system_settings:
            write_target:
                type: 'settings-store'
            read_target:
                type: 'settings-store'
```

To switch from Symfony configuration to the settings store for the first time, follow these steps:

1. Set your write target to `settings-store`:

```yaml
pimcore:
    config_location:
        system_settings:
            write_target:
                type: 'settings-store'
```

2. Save system settings in Pimcore Studio (*System* > *System Settings*).

3. Set your read target to `settings-store` as well:

```yaml
pimcore:
    config_location:
        system_settings:
            write_target:
                type: 'settings-store'
            read_target:
                type: 'settings-store'
```

## Localization and Internationalization (i18n/l10n)

These settings specify content languages used in documents (properties tab), objects (localized fields),
shared translations, and anywhere else an editor selects a language. Fallback languages currently apply
to localized fields in objects and shared translations.

## Debug

Debugging settings for Pimcore, including debug email addresses and debug admin translations.

## Website

System settings for the CMS part of Pimcore.

## Documents

Settings for documents including version steps, default values, and URL settings.

## Objects

Version steps for objects.

## Assets

Settings for assets including version steps.

## Access System Config in a PHP Controller

Access the system configuration by injecting `SystemSettingsConfig`:

```php
<?php

namespace App\Controller;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Configuration\DataObject\SystemSettingsConfig;
use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends FrontendController
{
    public function defaultAction(Request $request, SystemSettingsConfig $config): Response
    {
        $validLanguages = $config['general']['valid_languages'];
    }
}
```
