# Commandline Interface

Pimcore offers for some tasks a commandline interface. 

Use the following command to get a full list of available tasks: 

```php
php pimcore/cli/console.php list
``` 

The commandline interface is based on Pimcore console. See its [documentation](../09_Development_Tools_and_Details/11_Console_CLI.md) 
for more information and how to add custom tasks to it.
 
 
## Backend Search Reindex
This script recreates the search index for the backend search. 

```php
php pimcore/cli/console.php search-backend-reindex
``` 


## Cache Warming

This script is useful to prefill the cache with all items. 
Especially on high-traffic sites this can be very useful. 

Please read the latest help message by calling:
```php
php pimcore/cli/console.php cache:warming --help 
``` 


## Image & Video Thumbnail Generator

### Image
This script is useful to prefill the thumbnail cache. Especially on high-traffic sites this can be very useful 
(eg. generating thumbnails on a dedicated machine/cluster and sharing them via NFS), or just so save loading time.
 
Please read the latest help message by calling:
```php
php pimcore/cli/console.php thumbnails:image -h
``` 

### Video
Please read the latest help message by calling:
```php
php pimcore/cli/console.php thumbnails:video -h 
``` 


## MySQL Tools
This script is useful to optimize and warm up the database to increase the overall performance.
 
Using this script is only useful after the MySQL daemon was restarted (or system reboot). The script will then defragment 
all the tables and load them into the memory. This is only useful if your MySQL configuration (my.cnf) is specifically 
optimized for your data (innodb pool size, ... ).
 
Please read the latest help message by calling: 
```php
php pimcore/cli/console.php mysql-tools --help 
``` 