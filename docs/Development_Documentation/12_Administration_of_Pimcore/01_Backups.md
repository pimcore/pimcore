# Backup of Pimcore

Backup of systems is always important and needs the necessary attention. In terms of production relevant data, at minimum
following things need to be included into the backup: 
* Database
* File system folder `/website/var`

This is valid for a Pimcore default setup. If your custom solution adds production relevant data files, these locations need 
to be included in the backup too. Also if you overwrite path constants of Pimcore and store your assets, versions, etc. 
at different locations, they need to be included to the backup. 
 
Source code files are not included when just including `/website/var` into the backup since it is expected that they are 
in some kind of version control system like Github. If this is not the case, of course the whole document root of Pimcore
needs be included into the backup. 

## Backup Tools


### Backups in the administration interface

You can create full backups of the system directly in Pimcore. Just go to *Extras* > *Backup*. 
 
The backup includes the following directories: 
* `/pimcore`
* `/website`
* `/plugins`

All other directories in the document root are ignored and not part of the backup archive.


### Command-line Backup
Depending on the amount of content in your Pimcore installation, the backup can take up to some hours. Please use the 
commandline backup tool in this case, which is up to 10 times faster.
The cli backup-tool also gives you the possibility to create periodic (eg. cron-jobs) backups from your Pimcore installation. 

```php
php pimcore/cli/console.php backup -h
```

#### Just create a single backup into the backup-folder
```php
php pimcore/cli/console.php
``` 

#### Create a backup in a custom folder with a custom filename. 
If the destination file exists, it will be overwritten
```php
php pimcore/cli/console.php backup -f myBackupname -d /var/backups/ -o
```


### Restore backups
The backup file is an compressed zip archive which can be extracted in *nix systems with the commandline tool `unzip` 
on Windows systems you can use for example `7-Zip` or the built-in support in Explorer.
