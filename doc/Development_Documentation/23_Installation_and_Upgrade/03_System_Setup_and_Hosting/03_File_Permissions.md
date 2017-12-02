# File Permissions

Pimcore requires write access to the following directories: `/app/config`, `/bin`, `/composer.json`, `/pimcore`, `/var`, `/web/pimcore` and `/web/var`.   

If you know which user executes PHP on your system (PHP-FPM user, Apache user, ...), simply give write access to the appropriate user.
Execute the following commands on the shell (eg. via SSH, …) in your install directory - replace `YOURUSER` and `YOURGROUP` with your configuration:

```bash
chown -R YOURUSER:YOURGROUP app/config bin composer.json pimcore var web/pimcore web/var
```

Be aware that some dependencies could need to be able to write in others directories (like Ocramius/PackageVersions that manage package versions and need to be able to write in other directories during updates).
In such case, you should ensure to configure properly the permissions for each one, or more simply add write permissions to all the files by running `chown -R YOURUSER:YOURGROUP *`.
You can get further generic information about Symfony file permissions here: [Symfony file permissions](https://symfony.com/doc/current/setup/file_permissions.html).

To be able to execute cli tools (pimcore or symfony console for instance), you need to give execute permissions to the cli tools. Here it add execute permissions to the user and group:
```bash
chmod ug+x bin/*
```

On Debian systems (and most other Linux distributions) mostly the www-data user executes the PHP files, just execute the following commands in your install directory.

```bash
chown -R YOURUSER:YOURGROUP app/config bin composer.json pimcore var web/pimcore web/var
chmod ug+x bin/*
```
