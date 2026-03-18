---
title: Deployment Tools
description: Configuration management, class definitions, and console commands for deployment.
---

# Deployment Tools

Pimcore provides the following tools to support deployment processes.

## Pimcore Configurations

Pimcore has two categories of configuration:

**Symfony-only configurations** (`config/` directory) are standard Symfony YAML/PHP config files.
These are always file-based, included in version control, and deployed alongside code.
Use [Symfony configuration environments](https://symfony.com/doc/current/configuration.html#configuration-environments)
to define different values per deployment stage.

- [config/](https://github.com/pimcore/skeleton/tree/2026.x/config)
- [config/pimcore/](https://github.com/pimcore/skeleton/tree/2026.x/config/pimcore)

**Studio-editable configurations** (thumbnails, custom reports, static routes, perspectives,
document types, predefined properties, custom views, etc.) use the `LocationAwareConfigRepository`,
which supports two storage backends:

- **`symfony-config`** - YAML files on the filesystem (default directory: `var/config/`).
  Suitable for version control and file-based deployment.
  In production without debug mode, these files are compiled into the Symfony container
  and are read-only - changes on disk have no effect until the cache is rebuilt.
- **`settings-store`** - Database-backed key-value storage.
  Allows runtime editing through Pimcore Studio in production.

Each configuration type has independent read and write targets, configurable per environment.
See [Configuration Environments](../08_Development_Details/01_Configuration/01_Configuration_Environments.md)
for details on setting up read/write targets and handling production deployments.


## Pimcore Class Definitions

Pimcore class definitions are stored as PHP configuration files and can be added to
version control and deployed across stages.

The PHP configuration files and classes are written to `var/classes` by default.
To make a class read-only, create a copy at `config/pimcore/classes`.

The optional environment variable `PIMCORE_CLASS_DEFINITION_WRITABLE` controls write access:

- `0` - Disallow all write access, including creation of new classes.
- `1` - Allow modification of all classes, including those in `config/pimcore/classes` that are normally read-only.
- Not set - Classes in `config/pimcore/classes` are read-only; new classes are created in `var/classes`.

Use the environment variable `PIMCORE_CLASS_DEFINITION_DIRECTORY` to specify a custom directory
for class definitions instead of `var/classes` or `config/pimcore/classes`.

:::note
Changes to class definitions affect both configuration files and the database.
When deploying changes between stages, deploy database changes with
the `pimcore:deployment:classes-rebuild` command.
:::

Run `pimcore:deployment:classes-rebuild` after every code update to push changes to the database:

```bash
bin/console pimcore:deployment:classes-rebuild
```

To update only the database structure without dumping classes to the file system:

```bash
bin/console pimcore:deployment:classes-rebuild --db-only
```

To create new classes from configuration files in the database:

```bash
bin/console pimcore:deployment:classes-rebuild --create-classes
```

If you use [Composer's autoloader optimization](https://getcomposer.org/doc/articles/autoloader-optimization.md),
register newly created classes with:

```bash
composer dump-autoload --optimize
```

As an alternative, use the class export/import commands for JSON-based definitions:

```bash
bin/console pimcore:definition:import:objectbrick /brick_jsonfile_path.json

bin/console pimcore:definition:import:fieldcollection /collection_jsonfile_path.json

bin/console pimcore:definition:import:class /class_jsonfile_path.json
```


## Pimcore Console

The [Pimcore Console](../08_Development_Details/09_CLI_and_Pimcore_Console.md)
provides several useful commands for deployment.
Integrate these into custom deployment workflows as needed.

Run `bin/console list` for a full list of available commands.

### Useful Deployment Commands

| Command                                   | Description                                                                                                                       |
|-------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------|
| pimcore:mysql-tools                       | Optimize and warm up the MySQL database                                                                                           |
| pimcore:search-backend-reindex            | Re-index the backend search (requires SimpleBackendSearchBundle)                                                                  |
| pimcore:cache:clear                       | Clear Pimcore core caches                                                                                                         |
| cache:clear                               | Clear Symfony caches                                                                                                              |
| pimcore:cache:warming                     | [Warm up caches](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/02_System_Setup_and_Hosting/08_Performance_Guide.md#pimcore-caching-redis)        |
| pimcore:classificationstore:delete-store  | Delete a Classification Store                                                                                                     |
| pimcore:definition:import:class           | Import a class definition from a JSON export                                                                                      |
| pimcore:definition:import:customlayout    | Import a custom layout definition from a JSON export                                                                              |
| pimcore:definition:import:fieldcollection | Import a FieldCollection definition from a JSON export                                                                            |
| pimcore:definition:import:objectbrick     | Import an ObjectBrick definition from a JSON export                                                                               |
| pimcore:definition:import:units           | Import quantity value unit definitions from a JSON export                                                                         |
| pimcore:deployment:classes-rebuild        | Rebuild classes and database structure based on updated `var/classes/definition_*.php` files                                      |
| pimcore:thumbnails:image                  | Generate image thumbnails. Use `--processes` for parallel processing.                                                             |
| pimcore:thumbnails:optimize-images        | Optimize file size of all images in `public/var/tmp`                                                                              |
| pimcore:thumbnails:video                  | Generate video thumbnails. Use `--processes` for parallel processing.                                                             |

Find more about the Pimcore Console on the [dedicated page](../08_Development_Details/09_CLI_and_Pimcore_Console.md).


## Content Migration

Pimcore does not provide content migration between environments and does not recommend it.

Create and manage content in the production environment. Control visibility with built-in features:
publishing/unpublishing, [versioning](../05_Content_Management_Features/01_Versioning.md),
[scheduling](../05_Content_Management_Features/05_Scheduling.md), and editmode preview.

Editors should create and modify content on a single environment (typically production)
rather than maintaining content across multiple stages.

If content migration is necessary, it is always a project-specific task depending on the data model,
environments, and use cases. Use the PHP API for
[assets](../02_Assets/04_Working_with_Assets_via_PHP_API.md),
[objects](../03_Objects/02_Working_with_Objects_via_PHP_API.md), and
[documents](../01_Documents/14_Working_with_Documents_via_PHP_API.md).
