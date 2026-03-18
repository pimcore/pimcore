---
title: Version Control Systems
description: Git configuration and paths to exclude from version control.
---

# Version Control Systems

Pimcore generates temporary files, caches, and runtime data during operation.
Exclude these paths from version control to keep the repository clean.

Use the `.gitignore` from the Pimcore demo project as a starting point:

- [pimcore/demo-enterprise `.gitignore`](https://github.com/pimcore/demo-enterprise/blob/2026.x/.gitignore)

:::info Enterprise Repository
The `pimcore/demo-enterprise` repository requires access to the Pimcore enterprise package registry.
:::

Key directories to exclude:

- `var/cache/` - Symfony and Pimcore caches (rebuilt automatically)
- `var/log/` - Log files
- `var/tmp/` - Private temporary files (uploads, imports, previews)
- `var/versions/` - Element version dumps (large, runtime-generated)
- `var/recyclebin/` - Deleted element dumps
- `public/var/` - Public thumbnails and temporary assets
- `vendor/` - Composer dependencies (managed by `composer install`)
