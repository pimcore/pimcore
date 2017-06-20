# Editable Naming Strategies

Before Pimcore 5 build 54, Pimcore saved editable names in a way which couldn't always reliably determine an element's
hierarchy inside block elements. This caused problems when copy/pasting elements in editmode and when accessing
elements programmatically through the PHP API. See [https://github.com/pimcore/pimcore/issues/559](https://github.com/pimcore/pimcore/issues/559).

To address this issues, we implemented a new `nested` naming strategy which stores editable names in a more concise and
clear way and allows to access and modify (e.g. copy/paste) document elements more efficiently and reliably in
[https://github.com/pimcore/pimcore/issues/1467](https://github.com/pimcore/pimcore/issues/1467).

A comparison between the `legacy` and the `nested` naming strategy:

```
# the element referenced here would be "content[7].accordion[1].headline"
# in array notation

# legacy naming strategy
headlinecontent_accordioncontent77_1

# nested naming strategy
content:7.accordion:1.headline
```

> If you installed Pimcore 5 before build 54 or migrated Pimcore from version 4, you'll need to migrate your document
  structure to the new `nested` naming strategy. If you installed Pimcore after build 54 you don't need to migrate
  anything as your documents already use the new naming strategy.

You can check the currently configured naming strategy by issuing the following command:

```
$ bin/console debug:config pimcore documents.editables.naming_strategy

Current configuration for "pimcore.documents.editables.naming_strategy"
=======================================================================

legacy
```

As new Pimcore installations default to the `nested` naming strategy, the Pimcore update script for build 54 creates
a config file in `app/config/local/update_54_legacy_naming.yml` which configures your installation to use the legacy
naming strategy:

```yaml
# created by build 54 - see https://github.com/pimcore/pimcore/issues/1467
pimcore:
    documents:
        editables:
            naming_strategy: legacy
```

This configuration is important and needs to be included as otherwise all nested editables would lose their data. However
as the `app/config/local` directory is ignored in Git by default, please make sure you take care to track this configuration
in your projects (either by moving the config entry to a tracked config file or by tracking configurations in `app/config/local`
in your repository.


## Migration to the nested naming strategy

Migration to the `nested` naming strategy is implemented as console command which will guide you through the migration:

```
$ bin/console pimcore:documents:migrate-naming-strategy
```

There are currently 2 different strategies which can be used to migrate your editables:

* `render`: renders all documents to fetch all editable names. To make the render strategy work you must make sure that
  all your documents/templates can be rendered without errors.

* `analyze`: analyzes the DB structure and tries to fetch editable names to migrate from the existing editable names. As
  this can't always be reliably determined, you'll be prompted to resolve potential conflicts.

Which strategy you use depends on you and your project. The `render` strategy can resolve the mapping without user interaction,
but as it needs to render all documents it can take a long time (depending on your templates) and the amount of documents
and it demands that all your documents/templates can be rendered without errors.

The `analyze` strategy tries to parse the editable hierarchy directly from the database structure and is quite fast if
all editables can be automatically resolved. In cases of ambiguous editable names (e.g. 2 blocks named `content` and
`content1`) it may be needed that you need to resolve conflicts manually. Also, the `analyze` strategy is not able to
deal with orphaned elements which don't have any parent block, e.g. because the editable does not exist in the template
anymore and it's up to you to decide if you want to ignore the editable. In many cases, orphaned elements can be cleaned
up by opening and re-saving the failed document in the admin interface as this will remove any elements which don't exist
anymore.

**No matter which strategy you use, please make sure you have a proper backup before running the migration as the migration
is irreversible!**

You can try to run the migration with the `--dry-run` option first to see any conflicts without changing your actual elements.
Even in `--dry-run`, the migration will write a cache of successfully migrated documents, so if you run the command again,
cached documents don't need to be analyzed again.

> After completing the migration, make sure you configure Pimcore to use the `nested` naming strategy. You can do this by
  either removing the file created by the updater (Pimcore will default to `nested`) or updating your configuration to
  use `nested` for `pimcore.documents.editables.naming_strategy`
