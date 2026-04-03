---
title: Cleanup Data Storage
description: Manage versioning data, logs, temporary files, and recycle bin storage.
---

# Cleanup Data Storage

Pimcore handles most filesystem cleanup automatically. The following recommendations help reduce
and manage storage consumption.

## Versioning Data

### Reduce Version History Per Element

Pimcore stores version metadata in the database and the version data itself as compressed files (gzip)
in `var/versions`. Each version is a complete dump of the element at the time of creation -
Pimcore does not use differential or incremental storage.

This means versioning data can grow fast, especially for large assets.
Reduce the number of stored versions per element type (assets, objects, documents)
in Pimcore Studio under `System` > `System Settings` > `Documents/Objects/Assets`.

After reducing the value, run the cleanup manually
(it also runs automatically as part of regular maintenance):

```bash
bin/console pimcore:maintenance -j versioncleanup
```

#### Example

An asset of 100 MB with 10 versioning steps configured requires up to 1.1 GB of storage:
100 MB for the original asset plus 10 x 100 MB for the version dumps.

### Flush All Versions of a Type

To delete all versioning information for a specific type:

:::warning
The following commands delete all versioning information for the specified type.
Back up the database and `var/versions/` directory before running them.
:::

```bash
# Replace ### with the name of your database
# Use "object" or "document" instead of "asset" as needed
mysql -e "DELETE FROM ###.versions WHERE ctype='asset';"
rm -r var/versions/asset
```

## Logging Data

All log files are stored in `var/log/`. Pimcore rotates, compresses, and cleans up logs automatically:

- **Rotate**: when the file exceeds 200 MB
- **Compress**: immediately after rotating (gzip)
- **Delete**: after 30 days

Delete logs manually at any time.
To use a custom log rotator, deactivate the `logmaintenance` job:

```bash
bin/console pimcore:maintenance -J logmaintenance
```

## Temporary Files

Pimcore stores temporary files in two locations based on their visibility:

**Private temporary directory**: `var/tmp/`
Used for uploads, imports, exports, and page previews.

**Public temporary directory**: `public/var/tmp/`
Used for image, video, and document thumbnails served by the web application.

### Clearing Temporary Files

Clear temporary files for a specific thumbnail configuration:

```bash
# Clear temp files for a specific image thumbnail config
bin/console pimcore:thumbnails:clear --type=image --name=myThumbnail

# Clear temp files for a specific video thumbnail config
bin/console pimcore:thumbnails:clear --type=video --name=myThumbnail
```

To clear all thumbnails and the asset cache programmatically:

```php
use Pimcore\Tool\Storage;
use Pimcore\Db;

// Clear all public thumbnails and the thumbnail database cache
Storage::get('thumbnail')->deleteDirectory('/');
Db::get()->executeQuery('TRUNCATE TABLE assets_image_thumbnail_cache');

// Clear the asset cache (preview images, etc.)
Storage::get('asset_cache')->deleteDirectory('/');
```

Clear the private temporary directory using the global
`recursiveDelete()` helper (defined in `pimcore/lib/helper-functions.php`):

```php
// Deletes all files in the system temp directory but keeps the directory itself
recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY, false);
```

All temporary files can be deleted at any time.

:::warning
Deleting all files in `public/var/tmp/` has a significant performance impact
until all required thumbnails are regenerated.
:::

## Recycle Bin

Deleting items in Pimcore moves them to the recycle bin first. The recycle bin stores references
in the database and dumps the element contents into files in `var/recyclebin/`.

In Pimcore Studio, open `Quick Access` > `Recycle Bin` to review items or flush the entire bin.

To delete items based on age:

```bash
bin/console pimcore:recyclebin:cleanup --older-than-days=60
```

To flush the entire recycle bin manually (useful for automation or large bins):

:::warning
The following commands permanently delete all recycle bin data.
Back up the database and `var/recyclebin/` directory before running them.
:::

```bash
# Replace ### with the name of your database
mysql -e "TRUNCATE TABLE ###.recyclebin;"
rm -r var/recyclebin
```

:::warning
The recycle bin is an administrative tool that displays deleted elements from all users.
Due to the complexity of element deletion and restoration, reserve this tool
for administrators and advanced users.
:::

## Output Cache

When enabled, the full-page cache stores complete frontend responses (including headers)
in the cache.

Clear the output cache programmatically:

```php
use Pimcore\Cache;

// When a cache lifetime is set, the "output" tag is ignored by default during cache clear.
// Remove it from the ignored list so the next clear call affects output cache entries.
Cache::removeIgnoredTagOnClear('output');

// Clear the document cache
Cache::clearTags(['output', 'output_lifetime']);
```
