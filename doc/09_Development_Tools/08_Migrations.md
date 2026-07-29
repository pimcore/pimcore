---
title: Migrations
description: Doctrine Migrations integration with namespace filtering.
---

# Migrations

Evolving applications frequently need to migrate data and data structures.
Common examples include adding database columns or transforming data.

Pimcore integrates the
[Doctrine Migrations Bundle](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html)
for a migration framework. Follow the official guide linked above
for project or bundle-specific migrations.

Pimcore adds the `--prefix` option to all Doctrine Migrations commands,
allowing you to filter migrations by namespace (core, bundle, or project).

For bundle migrations, see the
[bundles documentation](../10_Extending_Pimcore/04_Pimcore_Bundle_Developers_Guide/01_Pimcore_Bundles/01_Installers.md).
Pimcore provides a customized abstract for bundles.


## Example Commands

```bash
# run migrations of the Pimcore core
bin/console doctrine:migrations:migrate --prefix=Pimcore\\Bundle\\CoreBundle

# run migrations of a certain bundle
bin/console doctrine:migrations:migrate --prefix=Vendor\\PimcoreExampleBundle

# generate a project specific migration
bin/console doctrine:migrations:generate --namespace=App\\Migrations

# run project specific migrations
bin/console doctrine:migrations:migrate --prefix=App\\Migrations
```


## Config Examples for Your Project (`config/config.yaml`)

```yml
doctrine_migrations:
    migrations_paths:
        'App\Migrations': '%kernel.project_dir%/src/Migrations'
```

## Run All Available Migrations After `composer update`

To run all available migrations after `composer update` (including bundle and app-specific migrations),
add the following to your `composer.json`:

```json
"post-update-cmd": [
    "./bin/console doctrine:migrations:migrate"
]
```
