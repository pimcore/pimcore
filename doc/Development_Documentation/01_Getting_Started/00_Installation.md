# Pimcore Installation

The following guide assumes you're using a typical LAMP environment, if you're using a different setup (eg. Nginx) 
or you're facing a problem, please visit the [Installation Guide](../23_Installation_and_Upgrade/README.md) section.

## 1. System Requirements

Please have a look at [System Requirements](../23_Installation_and_Upgrade/01_System_Requirements.md) and ensure your system is ready for Pimcore.

## 2. Install Pimcore & Dependencies

The easiest way to install Pimcore is from your terminal using Composer.
Change into the root folder of your project (**please remember project root != document root**):
  
```bash
cd /your/project
```

#### Choose a package to install
We offer 2 different installation packages, a demo package with exemplary blueprints and an empty skeleton package for experienced developers.

##### 1. Skeleton Package (only for experienced Pimcore developers)
```bash
COMPOSER_MEMORY_LIMIT=-1 composer create-project pimcore/skeleton my-project
```

##### Demo Package
```bash
COMPOSER_MEMORY_LIMIT=-1 composer create-project pimcore/demo my-project
```

Point the document root of your vhost to the newly created `/web` folder (eg. `/your/project/web`).
Keep in mind, that Pimcore needs to be installed **outside** of the **document root**.
Specific configurations and optimizations for your web server are available here:
[Apache](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md),
[Nginx](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md)

Pimcore requires write access to the following directories (relative to your project root): `/var`, `/web/var` ([Details](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/03_File_Permissions.md))

If you're running the installation using a [custom environment name](../21_Deployment/03_Multi_Environment.md), ensure you already have the right config files in place, e.g. `app/config/config_[env_name].yml`. 

## 3. Create Database

```bash
mysql -u root -p -e "CREATE DATABASE project_database charset=utf8mb4;"
```

For further information please visit out [DB Setup Guide](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/05_DB_Setup.md)

## 4. Launch Installer

```
cd ./my-project
./vendor/bin/pimcore-install
```

This launches the interactive installer with a few questions. Make sure that you set the `memory_limit` to at least `512M` in your php.ini file.   

> Note: Pimcore allows a fully automated installation process, read more here: [Advanced Installation Topics](./01_Advanced_Installation_Topics.md) 

##### Open Admin Interface
After the installer has finished, you can open the admin interface: `https://your-host.com/admin`

##### Debugging installation issues

The installer writes a log in `var/logs` which contains any errors encountered during the installation. Please
have a look at the logs as a starting point when debugging installation issues.


## 5. Maintenance Cron Job

```bash
*/5 * * * * /your/project/bin/console maintenance
```

Keep in mind, that the cron job has to run as the same user as the web interface to avoid permission issues (eg. `www-data`).

## 6. Additional Information & Help

If you would like to know more about the installation process or if you are having problems getting Pimcore up and running, visit the [Installation Guide](../23_Installation_and_Upgrade/README.md) section.

## 7. Further Reading

- [Advanced Installation Topics](./01_Advanced_Installation_Topics.md)
- [Apache Configuration](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md)
- [Nginx Configuration](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md)
- [Database Setup](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/05_DB_Setup.md)
- [Additional Tools Installation](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md)

Next up - [Directories Structure](./02_Directory_Structure.md)
