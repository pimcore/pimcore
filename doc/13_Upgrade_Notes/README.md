# Upgrade Notes

## Pimcore 2026.1.0

### Tasks to Do Prior the Update

#### Symfony 7.3+ Required

Pimcore 13 requires **Symfony 7.3 or higher**. All Symfony 6.x components are no longer supported.

1. Update all Symfony components to version 7.3 or higher
2. Test your application thoroughly with Symfony 7.x
3. Remove any explicit Symfony 6.x version constraints from your `composer.json`

#### Migrate Folder structure for email logs
The folder structure for email logs has changed to YYYY/MM/DD/\<log filename\>.
Please execute the command `pimcore:migrate:mail-logs-folder-structure` to move the files into the new folder structure or move the files manually.


#### Database Collation: utf8mb4_unicode_520_ci

Pimcore 2026.1 now explicitly uses `utf8mb4_unicode_520_ci` as the collation for all `utf8mb4` tables and columns. Previous versions specified `DEFAULT CHARSET=utf8mb4` without an explicit `COLLATE` clause in `install.sql` and Dao `CREATE TABLE` statements. Due to MySQL/MariaDB behavior, this caused the charset's built-in default collation (for example `utf8mb4_general_ci` on MySQL 5.7 / MariaDB or `utf8mb4_0900_ai_ci` on MySQL 8) to be used instead of the database-level default (`utf8mb4_unicode_520_ci`).

This mismatch can cause issues with foreign key constraints between tables that have different collations and may lead to unexpected sorting behavior.

**Important:** Some columns intentionally use a different collation (e.g. `utf8mb4_bin` for case-sensitive keys and JSON data). These columns must **not** be changed. The queries below only target columns that use the typical default, non-binary `utf8mb4` collations (`utf8mb4_general_ci` and `utf8mb4_0900_ai_ci`); adjust this list if your server uses a different default.

Use the following SQL to identify all affected tables and columns in your database:

```sql
-- List all tables with default non-binary utf8mb4 table collations
SELECT TABLE_NAME, TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'your_database_name'
  AND TABLE_TYPE = 'BASE TABLE'
  AND TABLE_COLLATION IN ('utf8mb4_general_ci', 'utf8mb4_0900_ai_ci')
ORDER BY TABLE_NAME;

-- List all columns with default non-binary utf8mb4 collations
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLLATION_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'your_database_name'
  AND COLLATION_NAME IN ('utf8mb4_general_ci', 'utf8mb4_0900_ai_ci')
ORDER BY TABLE_NAME, COLUMN_NAME;
```

### [General]
- Thumbnail generation adapters now implement `Video\AdapterInterface` and `Document\AdapterInterface` and improved adapters' initialization.
- The abstract class `Pimcore\Video\Adapter` has been removed. `Pimcore\Video\Adapter\Ffmpeg` now directly implements `Pimcore\Video\AdapterInterface`. If you extended `Pimcore\Video\Adapter`, update your class to implement `Pimcore\Video\AdapterInterface` directly.
- The hard-coded space between quantity value and unit has been removed for class definition quantity fields (e.g. `AbstractQuantityValue`, `QuantityValueRange`). Spacing and formatting between value and unit is now controlled by locale- and translation-based formatting instead of being fixed in the code.
- The `reset_password` rate limiter configuration has been moved to the studio-backend bundle and is no longer part of Pimcore's core configuration.
- Removed legacy Admin UI (Classic UI) `EditmodeListener`.
- Added support for PHP `8.5` and bumped minimum requirement of Symfony to `7.4`.
- Dropped support for PHP `8.3` and Symfony `6`.
- [QuantityValue] Introduced foreign key constraints on `__unit` columns in object store, query, localized, objectbrick and fieldcollection tables for `QuantityValue`, `InputQuantityValue` and `QuantityValueRange` fields. These constraints reference `quantityvalue_units(id)` with `ON DELETE SET NULL` and `ON UPDATE CASCADE`, ensuring referential integrity. The migration automatically cleans up orphaned unit references (setting them to `NULL`) and changes the `__unit` column type from `varchar(64)` to `varchar(50)` to match the referenced `quantityvalue_units.id` column. If you have custom unit IDs longer than 50 characters, they will be truncated.

#### Removed deprecated and discontinued bundles
The following bundles have been removed:
- GlossaryBundle
- SimpleBackendSearchBundle
- SeoBundle: dropped `http_error_log` feature and DB Table, and removed Document SEO Editor
- StaticRouteBundle
- WordExportBundle
- XliffBundle

### [DataObjects]

- Add a new optional `$parameters` argument to `Concrete::saveVersion()` to allow passing of arguments to events.
- Removed the following methods from `Pimcore\Model\DataObject\Service` as part of the Admin UI removal:
    - `calculateCellValue()`
    - `mapFieldname()`
    - `getDataForEditmode()` in `ManyToManyObjectRelation` and `AdvancedManyToManyObjectRelation` now uses `Element\Service::gridElementData()` instead of the removed `Service::gridObjectData()`. As a result, the returned data for each related object no longer includes computed grid columns (e.g. brick fields, localized fields, classification store values, or helper columns). Only the base element data (id, type, path, etc.) is returned. If you rely on additional field data being present in the editmode payload of these relations, you need to fetch it separately.


### [Database]
- All `utf8mb4` tables now use `utf8mb4_unicode_520_ci` as their default collation to match Doctrine's `default_table_options` 
  configuration. Columns inherit this collation unless a different one is explicitly defined (for example `utf8mb4_bin` 
  for case-sensitive keys). Previously, `install.sql` and Dao `CREATE TABLE` statements specified `DEFAULT CHARSET=utf8mb4` 
  without an explicit `COLLATE` clause, which caused MySQL/MariaDB to assign the charset's built-in default collation 
  (`utf8mb4_general_ci`) instead of the intended `utf8mb4_unicode_520_ci`. 
  Existing installations need to update the collation of their tables and columns manually, details see 
  'Tasks to Do Prior the Update' chapter above. 

### [Models]
- Added a new optional `$parameters` argument to `AbstractUser::save()` and `AbstractUser::delete()`, as well as their interface methods, to allow passing of arguments to `UserRoleEvent`.

### [Generic Execution Engine]
- Added an `$offset` parameter to support paging in `getRunningJobsByUserId()` in `JobRunRepositoryInterface`.
- Added the possibility to pass an optional `$criteria` array to `getTotalCount()`, `getJobRunById()`, `getJobRunsByUserId()` and `getRunningJobsByUserId()` in `JobRunRepositoryInterface`.
- Added the possibility to pass optional `$ownerId` and `$executionContext` parameters to `getTotalCount()`.

### [Installer]

The installer has been completely redesigned with a **profile-based architecture**. The old individual CLI options for `pimcore:install` have been removed, and the command now uses a profile-driven setup via `--install-profile`.

#### New Command Invocation

The installer is now invoked with:

```bash
vendor/bin/pimcore-install --install-profile=App\\Install\\MyProfile
```

If you have scripts or CI pipelines that invoke `pimcore:install` with the old options, update them to use `--install-profile` with a profile class. Create an install profile implementing `InstallProfileInterface` for your project.

Please see the documentation for further information: https://docs.pimcore.com/platform/

#### Removed CLI Options

The following CLI options have been **removed**:

| Old Option | Replacement |
|---|---|
| `--mysql-host-socket` | Use `DATABASE_URL` env var (Doctrine DSN format) |
| `--mysql-username` | Use `DATABASE_URL` env var |
| `--mysql-password` | Use `DATABASE_URL` env var |
| `--mysql-database` | Use `DATABASE_URL` env var |
| `--mysql-port` | Use `DATABASE_URL` env var |
| `--mysql-ssl-cert-path` | Use `DATABASE_URL` env var |
| `--encryption-secret` | Use `PIMCORE_ENCRYPTION_SECRET` env var directly |
| `--instance-identifier` | Use `PIMCORE_INSTANCE_IDENTIFIER` env var directly |
| `--product-key` | Use `PIMCORE_PRODUCT_KEY` env var directly |
| `--install-bundles` | Bundles are now defined by the install profile's `getBundles()` method |
| `--skip-database-structure` | Use `InstallStepFilterInterface` in your profile to skip steps |
| `--skip-database-data` | Use `InstallStepFilterInterface` in your profile to skip steps |
| `--skip-database-data-dump` | Use `InstallStepFilterInterface` in your profile to skip steps |
| `--skip-database-config` | Configuration is now written to `.env.local`; to avoid writing installer-generated config, use `InstallStepFilterInterface` to skip `WriteEnv` and, if needed, `WriteDoctrineConfig` |
| `--skip-product-registration-config` | Configuration is now written to `.env.local`; to avoid writing installer-generated config, use `InstallStepFilterInterface` to skip `WriteEnv` |
| `--only-steps` | Use `InstallStepFilterInterface` to control which `InstallStep` enum values are skipped |

The options `--admin-username` and `--admin-password` are **retained** but now also accept the env vars `PIMCORE_ADMIN_USER` and `PIMCORE_ADMIN_PASSWORD` respectively.

#### New CLI Options

| New Option | Description |
|---|---|
| `--install-profile` | **(Required)** FQCN of the install profile class implementing `InstallProfileInterface` |
| `--env-definition` | FQCN(s) of additional `EnvVarDefinitionInterface` implementations (repeatable) |
| `--post-install-commands` | FQCN(s) of `PostInstallCommandsProviderInterface` implementations (repeatable) |
| `--skip-validation` | Skip env var validation (no value = skip all; with value = skip by key/class/FQCN) |

#### Removed Environment Variables (`PIMCORE_INSTALL_*` Prefix)

All `PIMCORE_INSTALL_*` environment variables have been **removed**. The old installer derived env vars by prepending `PIMCORE_INSTALL_` to the uppercased option name. These are replaced by standard env var names:

`DATABASE_URL` uses Doctrine DSN syntax and can describe either a TCP connection (for example `mysql://user:pass@host:3306/dbname`) or a unix socket connection.

| Old Env Var | New Env Var |
|---|---|
| `PIMCORE_INSTALL_ADMIN_USERNAME` | `PIMCORE_ADMIN_USER` |
| `PIMCORE_INSTALL_ADMIN_PASSWORD` | `PIMCORE_ADMIN_PASSWORD` |
| `PIMCORE_INSTALL_MYSQL_HOST_SOCKET` | `DATABASE_URL` (Doctrine DSN format, e.g. `mysql://user:pass@localhost/dbname?unix_socket=/var/run/mysqld/mysqld.sock`) |
| `PIMCORE_INSTALL_MYSQL_USERNAME` | `DATABASE_URL` |
| `PIMCORE_INSTALL_MYSQL_PASSWORD` | `DATABASE_URL` |
| `PIMCORE_INSTALL_MYSQL_DATABASE` | `DATABASE_URL` |
| `PIMCORE_INSTALL_MYSQL_PORT` | `DATABASE_URL` |
| `PIMCORE_INSTALL_MYSQL_SSL_CERT_PATH` | `DATABASE_URL` |
| `PIMCORE_INSTALL_ENCRYPTION_SECRET` | `PIMCORE_ENCRYPTION_SECRET` |
| `PIMCORE_INSTALL_INSTANCE_IDENTIFIER` | `PIMCORE_INSTANCE_IDENTIFIER` |
| `PIMCORE_INSTALL_PRODUCT_KEY` | `PIMCORE_PRODUCT_KEY` |
| `PIMCORE_INSTALL_INSTALL_BUNDLES` | Removed — bundles are defined by the profile |

#### Configuration Output Changes

The installer no longer writes the former local/user configuration YAML files for database and product registration settings. These values are now written to **`.env.local`** using Symfony Flex-style section markers (`###> section-name ###` / `###< section-name ###`). The installer still generates `config/packages/doctrine_mapping_types.yaml` for Doctrine mapping types.

| Old Config File | New Location |
|---|---|
| `config/local/database.yaml` | `.env.local` (`DATABASE_URL`) |
| `config/local/product_registration.yaml` | `.env.local` (`PIMCORE_ENCRYPTION_SECRET`, `PIMCORE_INSTANCE_IDENTIFIER`, `PIMCORE_PRODUCT_KEY`) |
| `system.yml` / `system.template.yml` | Legacy system config files are no longer written by the installer |

#### Removed Classes and Events

- `Pimcore\Bundle\InstallBundle\SystemConfig\ConfigWriter` — removed (no more YAML config writing)
- `Pimcore\Bundle\InstallBundle\Event\BundleSetupEvent` — removed (bundles are now defined by the profile)
- `Pimcore\Bundle\InstallBundle\DependencyInjection\Configuration` — removed (no more `pimcore_install.parameters.database_credentials` config tree)
- The `config/installer.yaml` option `pimcore_install.parameters.database_credentials` is no longer supported. Use `DATABASE_URL` env var or the interactive installer prompts instead.

#### Two-Phase Architecture

The installer now runs in two phases:
1. **Phase 1** (lightweight `InstallerKernel`): Collects and validates all env vars from the profile's `EnvVarDefinitionInterface` implementations, writes `.env.local`, writes Doctrine config.
2. **Phase 2** (real `App\Kernel`): Sets up the database, imports data sources, creates admin user, registers and installs bundles, runs post-install commands.

#### Profile Extensibility

Install profiles can implement additional interfaces for advanced control:
- `InstallStepFilterInterface` — skip specific install steps (useful for PaaS environments)
- `PostInstallHookInterface` — run custom PHP code near the end of phase 2, before finalization steps such as cache clearing and install marker cleanup
- `DataSourceInterface` — import SQL dumps or other data during installation

### [OpenSearch / Elasticsearch DSN Configuration]

Search engine configuration now uses DSN-based env vars instead of separate host/port/authentication parameters:

- **OpenSearch:** `PIMCORE_OPENSEARCH_DSN=opensearch://admin:admin@localhost:9200?ssl=true`
- **Elasticsearch:** `PIMCORE_ELASTICSEARCH_DSN=elasticsearch://elastic:changeme@localhost:9200`

The DSN is parsed at runtime in the client factory.
The old configuration approach with separate `hosts` arrays in YAML is replaced by a single `dsn` config option.

### [Messenger Transport DSN Changes]

**This is a breaking change for existing installations.**

The `PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX` env var (previously named `PIMCORE_MESSENGER_TRANSPORT_DSN`) format has changed.
It must now include a **trailing separator** for queue name concatenation, because Pimcore bundle configs append queue names directly to this value.

**Before (old format):**
```
PIMCORE_MESSENGER_TRANSPORT_DSN=doctrine://default
```

**After (new format):**
```
# Doctrine
PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX=doctrine://default?queue_name=

# AMQP
PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX=amqp://guest:guest@rabbit:5672/%2f/

# Redis
PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX=redis://localhost:6379/
```

All Pimcore bundle transport configs now use the container parameter `%pimcore.messenger.transport_dsn_prefix%` with direct concatenation:

```yaml
framework:
    messenger:
        transports:
            pimcore_core: '%pimcore.messenger.transport_dsn_prefix%pimcore_core'
            pimcore_maintenance: '%pimcore.messenger.transport_dsn_prefix%pimcore_maintenance'
```

A container-level default is provided in `bundles/CoreBundle/config/pimcore/default.yaml`:

```yaml
parameters:
    env(PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX): 'doctrine://default?queue_name='
```
If you have `PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX` (or the old `PIMCORE_MESSENGER_TRANSPORT_DSN`) explicitly set in your `.env` or environment, update its value to include the trailing separator and use the new name. For Doctrine, change `doctrine://default` to `doctrine://default?queue_name=`. Failure to do this will result in invalid transport DSNs like `doctrine://defaultpimcore_core`.
If you have custom transport definitions in your bundle or project YAML that hardcode `doctrine://default?queue_name=`, replace them with `'%pimcore.messenger.transport_dsn_prefix%'` concatenation to support backend-agnostic transport switching.

### Symfony Templating Component Removed

The `Symfony\Component\Templating\EngineInterface` and related services have been completely removed.

**What's Removed:**

-   `pimcore.templating.engine.delegating` service
-   `Symfony\Component\Templating\EngineInterface` support
-   `Pimcore\Templating\TwigDefaultDelegatingEngine` class

**Action Required:**
Update your code to use `Twig\Environment` directly instead of `EngineInterface`.

### QuantityValue Formatting Changes

-   The space between QuantityValue value and unit is going to be removed. Please make sure any custom code that relies on the space is updated accordingly.

### Deprecated Kernel Extension Hooks

Overriding `Pimcore\Kernel::configureContainer()` and `Pimcore\Kernel::configureRoutes()` is now deprecated and will be removed in **Pimcore 2027.1**.

These methods were exposed as `protected` on `Pimcore\Kernel` via trait aliases of `Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait`'s **private** methods. Symfony does not consider them part of its public API and has changed their signatures between minor versions, which can break Pimcore subclasses on Symfony upgrades.

**Migration:**

Replace overrides of `configureContainer()` with one of the following stable, public extension points:

-   Move container configuration to `config/packages/*.yaml` (or `config/packages/<env>/*.yaml`).
-   Add a [compiler pass](https://symfony.com/doc/current/service_container/compiler_passes.html) for programmatic container manipulation.
-   Override the public `Pimcore\Kernel::registerContainerConfiguration(LoaderInterface $loader)` method directly. Call `parent::registerContainerConfiguration($loader)` first, then load additional configuration via `$loader->load(...)`.

Replace overrides of `configureRoutes()` with one of the following:

-   Move routes to `config/routes/*.yaml`.
-   Register a custom routing loader as a service tagged `routing.loader`.

