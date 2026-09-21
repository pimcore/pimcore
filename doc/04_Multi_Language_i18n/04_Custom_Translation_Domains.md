---
title: Custom Translation Domains
description: "Register custom translation domains in Pimcore to isolate translations for bundles and application modules."
---

# Custom Translation Domains

Pimcore ships with two built-in translation domains: `messages` for shared/website translations and `studio` for
Pimcore Studio UI translations. Bundles and application modules can register additional
domains to keep their translations separate from the core domains.

## How Translation Domains Work

Each translation domain maps to its own database table following the naming convention `translations_{domain}`. For
example, the default `messages` domain stores its entries in `translations_messages`, while the `studio` domain uses
`translations_studio`.

Pimcore defines the default domains in `bundles/CoreBundle/config/pimcore/default.yaml`:

```yaml
pimcore:
    translations:
        domains:
            - messages
            - studio
```

When you register a custom domain, Pimcore validates it at runtime and makes it available through the standard Symfony
Translator component. The domain also appears as a filter option in the Pimcore Studio translations view.

## Registering a Custom Domain

Registering a custom domain requires two steps: declaring the domain in your bundle configuration and creating the
corresponding database table.

### Step 1: Declare the Domain in Configuration

Add your domain to `pimcore.translations.domains` in your bundle's config file. For example, the Portal Engine bundle
registers its frontend domain in `src/Resources/config/pimcore/config.yml`:

```yaml
pimcore:
    translations:
        domains:
            - portal_engine_frontend
```

This tells Pimcore to recognize `portal_engine_frontend` as a valid translation domain.

### Step 2: Create the Database Table

Your bundle must create the `translations_{domain}` table during installation. Use a Doctrine migration or your bundle's
installer to set up the required schema. The table needs the following structure:

```php
use Doctrine\DBAL\Schema\Schema;

public function up(Schema $schema): void
{
    $domain = 'your_domain';

    if ($schema->hasTable('translations_' . $domain)) {
        return;
    }

    $table = $schema->createTable('translations_' . $domain);

    $table->addColumn('key', 'string', ['length' => 190, 'notnull' => true]);
    $table->addColumn('type', 'string', ['length' => 10]);
    $table->addColumn('language', 'string', ['notnull' => true, 'length' => 10]);
    $table->addColumn('text', 'text');
    $table->addColumn('creationDate', 'integer', ['unsigned' => true, 'length' => 11]);
    $table->addColumn('modificationDate', 'integer', ['unsigned' => true, 'length' => 11]);
    $table->addColumn('userOwner', 'integer', ['unsigned' => true, 'length' => 11]);
    $table->addColumn('userModification', 'integer', ['unsigned' => true, 'length' => 11]);

    $table->addIndex(['language'], 'language');
    $table->setPrimaryKey(['key', 'language']);
}
```

This pattern mirrors the approach used by `TableInstaller::addTranslationDomainTable()` in the Portal Engine bundle
(available in the Enterprise edition).

## Using Custom Domain Translations

Once registered, you can reference your custom domain in Twig templates and PHP code just like the built-in domains.

In Twig:

```twig
{{ 'welcome_message'|trans({}, 'your_domain') }}
```

In PHP:

```php
$translatedValue = $translator->trans('welcome_message', [], 'your_domain');
```

The translator resolves the key against the `translations_your_domain` table for the current locale, following the same
fallback logic as shared translations.
