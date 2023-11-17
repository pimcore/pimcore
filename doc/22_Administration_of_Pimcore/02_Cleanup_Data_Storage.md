# Cleanup Data Storage

In general Pimcore is quite maintenance-free in terms of cleaning up the filesystem from temporary files, log files, 
versioning information and other generated data. However there are some tweaks you can use to reduce or cleanup your 
storage footprint on your filesystem. 

## Versioning Data
### Reduce the Amount of Versioning Steps of Assets, Objects and Documents
Pimcore stores the meta-data for version in the database, however, the data itself is stored on the filesystem 
(`var/versions`) as compressed files (gzip) to keep the database as lean as possible. 
It's important to know that Pimcore stores complete dumps of the data at the time the version is created, it doesn't 
use any kind of data differential/incremental or deduplication for several reasons. 
This means that the versioning data can grow very fast, especially when dealing with huge Assets. 
You can reduce the amount of restore points individually for Assets, Objects and Documents in the System Settings. 

After you have reduced the value, it's recommended to run the following command manually 
(it would also run automatically as part of the regular maintenance script): 
```bash
./bin/console pimcore:maintenance -j versioncleanup
```

#### Example
Assuming an Asset, 100MB in size, in system settings 10 versioning steps are configured. Every time the Asset get's saved
a new dump of the file is created, so the max. space required for this particular Asset is 1.1 GB 
(100MB for the original Asset + 10 x 100MB for the version dumps). 

### Flush all Versions of a certain Type
Sometimes it's necessary to clean all versioning information for a certain type, eg. for Assets or Objects. 
The easiest way is to do this manually with the following commands: 

**WARNING: The following commands will delete all versioning information of your installation**

```bash
// replace ### with the name of your database
// you can also use "object" or "document" instead of "asset"
mysql -e "DELETE FROM ###.versions WHERE ctype='asset';"
rm -r var/versions/asset
```

## Logging Data
All logging information is located in `var/log/`. Pimcore rotates & compresses and cleans up the logs automatically: 
Rotate: when the file is bigger than 200MB  
Compress: immediately after rotating (gzip)  
Delete: After 30 days 

Logs can be deleted manually at any time.  
It's also possible to use a custom log rotator, for this purpose please deactivate the `logmaintenance` job in your
maintenance command: `./bin/console pimcore:maintenance -J logmaintenance`

## Temporary Files
Pimcore stores temporary files in 2 different locations, depending on whether they are public accessible or not.   
**Private temporary directory**: `var/tmp/`  
Used for uploads, imports, exports, page, previews, ...  
**Public temporary directory**: `public/var/tmp/`  
Used for image/video/document thumbnails used in the web-application. 
  
### Clearing Temporary Files
```php
// clear public files
Tool\Storage::get('thumbnail')->deleteDirectory('/');
Db::get()->executeQuery('TRUNCATE TABLE assets_image_thumbnail_cache');

Tool\Storage::get('asset_cache')->deleteDirectory('/');

// clear system files
recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY, false);
```
All temporary files can be deleted at any time.   
**WARNING: Deleting all files in `public/var/tmp/` can have a huge impact on performance until all needed thumbnails are generated again.**

## Recycle Bin
Deleting items in Pimcore moves them to the recycle bin first. The recycle bin works quite similar to the versioning, 
so the references are kept in the database but the contents itself are dumped into files in `var/recyclebin/`.   
In the admin user-interface, under *Tools* > *Recycle Bin*, you can review items in the bin or flush the entire content. 

If you need to delete items based on how long they were stored in the recycle bin, the following command may come in handy: 
```bash
./bin/console  pimcore:recyclebin:cleanup --older-than-days=60
```
It's also possible to flush the entire bin manually, this is especially useful when automating this process, or if you have a huge 
amount of items in your recycle bin:   
```bash
// replace ### with the name of your database
mysql -e "TRUNCATE TABLE ###.recyclebin;"
rm -r var/recyclebin
```



**WARNING: The recycle bin is an administrative tool that displays any user's deleted elements. 
Due to the nature and complexity of the elements deletion and restoration process, this tool should be reserved for administrator and advanced users**

## Output Cache
When enabled, the full page cache stores the whole frontend request response including the headers from a request and stores it into the cache. 

The output cache can be cleared with the following snippet:
```php
// remove "output" out of the ignored tags, if a cache lifetime is specified
Cache::removeIgnoredTagOnClear('output');

// empty document cache
Cache::clearTags(['output', 'output_lifetime']);
```
