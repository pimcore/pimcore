---
title: Maintenance Mode
description: Restrict access during maintenance and customize the maintenance page.
---

# Maintenance Mode

Pimcore offers a maintenance mode that restricts access to the user who enabled it.
It is session-based: no other user can access the website or Pimcore Studio.

All other users see a
[default "Temporarily not available" page](https://github.com/pimcore/pimcore/blob/2026.x/bundles/CoreBundle/public/misc/maintenance.html).

Maintenance scripts and headless executions of Pimcore are also blocked.

Enable or disable maintenance mode via the console:

```bash
bin/console pimcore:maintenance-mode --enable
bin/console pimcore:maintenance-mode --disable
```

## Customize Maintenance Page

Overwrite the `Pimcore\Bundle\CoreBundle\EventListener\MaintenancePageListener` service
in `config/services.yaml`.

```yaml
Pimcore\Bundle\CoreBundle\EventListener\MaintenancePageListener:
    calls:
        - [loadTemplateFromResource, ['@@App/Resources/misc/maintenance.html']]
```

Use `loadTemplateFromPath` if the file is located outside a bundle:

```yaml
Pimcore\Bundle\CoreBundle\EventListener\MaintenancePageListener:
    calls:
        - [loadTemplateFromPath, ['/templates/maintenance.html']]
```
