# File Permissions

Pimcore requires write access to the following directories: `/web/var` and `/pimcore`.  

If you know which user executes PHP on your system (PHP-FPM user, Apache user, ...), simply give write access to the appropriate user.
Execute the following commands on the shell (eg. via SSH, …) - replace `YOURUSER` and `YOURGROUP` with your configuration:

```bash
chown -R YOURUSER:YOURGROUP web/var var pimcore
```

On Debian systems (and most other Linux distributions) mostly the www-data user executes the PHP files, just execute the following commands in your install directory.

```bash
chown -R www-data:www-data web/var var pimcore
```
