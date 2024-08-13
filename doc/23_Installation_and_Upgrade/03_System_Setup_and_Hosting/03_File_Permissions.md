# File Permissions

Pimcore requires write access to the following directories: `/var` and `/public/var`.   

If you know which user executes PHP on your system (PHP-FPM user, Apache user, ...), simply give write access to the appropriate user.
Execute the following commands on the shell (eg. via SSH, …) in your install directory - replace `YOURUSER` and `YOURGROUP` with your configuration:

```bash
chown -R YOURUSER:YOURGROUP var public/var
```

You can get further generic information about Symfony file permissions here: [Symfony file permissions](https://symfony.com/doc/current/setup/file_permissions.html).

To be able to execute cli tools (pimcore or symfony console for instance), you need to give execute permissions to the cli tools. Here it add execute permissions to the user and group:
```bash
chmod ug+x bin/*
```
