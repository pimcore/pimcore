# Pimcore Installation

The following guide assumes your're using a typical LAMP environment, if you're using a different setup (eg. Nginx) or facing a problem, please visit the [Installation Guide](../23_Installation_and_Upgrade/README.md) section.

## 1. System Requirements

Please have a look at [System Requirements](../23_Installation_and_Upgrade/01_System_Requirements.md) and ensure your system is ready for Pimcore.

## 2. Install Pimcore Sources

The easiest way to install Pimcore is from your terminal using our installer package.
We additionally provide a [Composer based install guide](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/04_Install_via_Composer.md) and of course you can install Pimcore also without the help of the command line using your favorite tools (SFTP, ...).

Change into the root folder of your project (**please remember project root != document root**):

```bash
cd /your/project
```

Pimcore is offering [2 installation packages](https://pimcore.com/en/download) for different use-cases:

| Version | Description |
|--------------------|---------------------------------------------------------------------------------|
| **Latest Release** | The latest release version of Pimcore. Good choice for all purposes ;-)         |
| **Nightly Build**  | Daily released version. Shouldn't be used in production but ok for development. |

### Download a Package:

```bash
wget https://pimcore.com/download-5/pimcore-latest.zip -O pimcore-install.zip
```

Unzip the installer package into the current folder (project root):

```bash
unzip pimcore-install.zip
```

Point the document root of your vhost to the newly created `/web` folder out of the ZIP archive (eg. `/your/project/web`).
Keep in mind, that Pimcore needs to be installed **outside** of the **document root**.
Specific configurations and optimizations for your webserver are available here:
[Apache](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md),
[Nginx](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md)

Pimcore requires write access to the following directories (relative to your project root): `/var`, `/web/var` and `/pimcore`
([Details](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/03_File_Permissions.md))

> No CLI? Click [Latest Release](https://pimcore.com/download/pimcore-latest.zip) or [Nightly Build](https://www.pimcore.org/download/pimcore-data.zip) to download the package with your browser and extract/upload Pimcore manually on your server (outside document root!).

## 3. Create Database

```bash
mysql -u root -p -e "CREATE DATABASE project_database charset=utf8mb4;"
```

For further information please visit out [DB Setup Guide](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/05_DB_Setup.md)

## 4. Launch Pimcore and Finish Installation

### Web installer

Finish the Pimcore installation by accessing the URL (eg. `https://your-host.com/install`) in your web browser.

1. Fill in the required fields (database + admin user)
2. Press ***Check Requirements*** to check if your system is ready for Pimcore
3. Click ***Install Now!***
4. Remove the file `web/install.php` as it isn't needed anymore

### CLI installer

Alternatively, you can use the CLI installer to install pimcore. The installer will guide you through the installation
interactively.

```
$ bin/install pimcore:install
```

Every parameter can be set as option to make the install automated. The `--no-interaction` flag will avoid any interactive
prompts:

```
$ bin/install pimcore:install --profile demo-basic \
  --admin-username admin --admin-password admin \
  --mysql-username username --mysql-password password --mysql-database pimcore5 \
  --no-interaction
```

To avoid having to pass sensitive data (e.g. DB password) as command line option, you can also set each parameter as env
variable. See `bin/install pimcore:install --help` for details. Example:

```
$ PIMCORE_INSTALL_MYSQL_USERNAME=username PIMCORE_INSTALL_MYSQL_PASSWORD=password bin/install pimcore:install \
  --profile demo-basic \
  --admin-username admin --admin-password admin \
  --mysql-database pimcore5 \
  --no-interaction
```

Please also remove the `web/install.php` file after the installation is complete.


### Preconfiguring the installer

You can preconfigure the values used by the installer by adding a config file which sets values for profile and database
credentials. This is especially useful when installing Pimcore on platforms where credentials are available via env vars
instead of having direct access to them. To preconfigure the installer, add a config file in `app/config/installer.yml` 
(note: the file can be of any format supported by Symfony's config, so you could also use xml or php as format) configure
the `pimcore_installer` tree:

```yaml
# app/config/installer.yml

pimcore_install:
    parameters:
        profile: demo-basic
        database_credentials:
            user:                 username
            password:             password
            dbname:               pimcore5
            
            # env variables can be directly read with the %env() syntax
            # see https://symfony.com/blog/new-in-symfony-3-2-runtime-environment-variables
            host:                 %env(DB_HOST)
            port:                 %env(DB_PORT)
```

You can also configure the installer to not copy the profile files or to symlink them (useful for development). See the
full tree of available options:

```yaml
# output of bin/install config:dump-reference pimcore_install

# Default configuration for extension with alias: "pimcore_install"
pimcore_install:

    # Shows an info message on the installation screen
    info_message:         null
    files:

        # Defines if profile files should be installed. If this is false, only the DB will be installed.
        install:              true

        # Symlink files instead of copying them
        symlink:              false
    parameters:

        # The install profile to use
        profile:              null
        database_credentials:
            user:                 ~
            password:             ~
            dbname:               ~
            host:                 ~
            port:                 ~
            unix_socket:          ~
```


### Debugging installation issues

The installer writes a log in `var/installer/logs` which contains any errors encountered during the installation. Please
have a look at the logs as a starting point when debugging installation issues.


## 5. Maintenance Cron Job

```text
*/5 * * * * /your/project/bin/console maintenance
```

Keep in mind, that the cron job has to run as the same user as the web interface to avoid permission issues (eg. `www-data`).

### 5.1 Add some randomness (optional)

In case you've got multiple Pimcore Projects on one (Development-)Server, you can add some randomness to the start times of the cron Jobs to mitigate the risk of multiple tasks starting in parallel.

```bash
# We need bash since RANDOM is a bash builtin
SHELL=/bin/bash

*/5 * * * * sleep $[ ( $RANDOM % 120 ) + 1 ]s ; /your/project/bin/console maintenance
```

## 6. Additional Information & Help

If you would like to know more about installation process or if you are having problems getting Pimcore up and running, visit the [Installation Guide](../23_Installation_and_Upgrade/README.md) section.

## 7. Further Reading

- [Apache Configuration](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md)
- [Nginx Configuration](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md)
- [Database Setup](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/05_DB_Setup.md)
- [Additional Tools Installation](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md)

Next up - [Directories Structure](./02_Directory_Structure.md)
