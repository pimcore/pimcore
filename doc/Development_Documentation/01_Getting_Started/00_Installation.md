# Pimcore Installation

<div class="alert alert-warning">
<strong>IMPORTANT:</strong> 
This is the installation guide for the BETA version of Pimcore 5.4.
Looking for the current stable [5.3 installation guide, click here](https://github.com/pimcore/pimcore/blob/pimcore-5.3.x/doc/Development_Documentation/01_Getting_Started/00_Installation.md). 
</div>    

The following guide assumes your're using a typical LAMP environment, if you're using a different setup (eg. Nginx) 
or facing a problem, please visit the [Installation Guide](../23_Installation_and_Upgrade/README.md) section.

## 1. System Requirements

Please have a look at [System Requirements](../23_Installation_and_Upgrade/01_System_Requirements.md) and ensure your system is ready for Pimcore.

## 2. Install Pimcore & Dependencies

The easiest way to install Pimcore is from your terminal using Composer.
Change into the root folder of your project (**please remember project root != document root**):
  
```bash
cd /your/project
```

#### Choose a package to install
We're offering 4 different installation packages, 3 demo packages and one skeleton for experienced developers.

##### 1. Skeleton Package (only for experienced Pimcore developers)
```bash
COMPOSER_MEMORY_LIMIT=3G composer create-project pimcore/skeleton:dev-master my-project
```

##### 2. Basic Demo Package (PHP Templates)
```bash
COMPOSER_MEMORY_LIMIT=3G composer create-project pimcore/demo-basic:dev-master my-project
```

##### 3. Basic Demo Package (Twig Templates)
```bash
COMPOSER_MEMORY_LIMIT=3G composer create-project pimcore/demo-basic-twig:dev-master my-project
```

##### 4. Advanced Demo Package (E-Commerce, PIM, MDM, DAM, ...)
```bash
COMPOSER_MEMORY_LIMIT=3G composer create-project pimcore/demo-ecommerce:dev-master my-project
```

Point the document root of your vhost to the newly created `/web` folder (eg. `/your/project/web`).
Keep in mind, that Pimcore needs to be installed **outside** of the **document root**.
Specific configurations and optimizations for your webserver are available here:
[Apache](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md),
[Nginx](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md)

Pimcore requires write access to the following directories (relative to your project root): `/var`, `/web/var`
([Details](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/03_File_Permissions.md))

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

This launches the interactive installer with a few questions.   

> Note: Pimcore allows a fully automated installation process, read more here: [Advanced Installation Topics](./01_Advanced_Installation_Topics.md) 

##### Open Admin Interface
After the installer has finished, you can open the admin interface: `https://your-host.com/admin`

##### Debugging installation issues

The installer writes a log in `var/installer/logs` which contains any errors encountered during the installation. Please
have a look at the logs as a starting point when debugging installation issues.


## 5. Maintenance Cron Job

```text
*/5 * * * * /your/project/bin/console maintenance
```

Keep in mind, that the cron job has to run as the same user as the web interface to avoid permission issues (eg. `www-data`).

## 6. Additional Information & Help

If you would like to know more about installation process or if you are having problems getting Pimcore up and running, visit the [Installation Guide](../23_Installation_and_Upgrade/README.md) section.

## 7. Further Reading

- [Advanced Installation Topics](./01_Advanced_Installation_Topics.md)
- [Apache Configuration](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md)
- [Nginx Configuration](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md)
- [Database Setup](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/05_DB_Setup.md)
- [Additional Tools Installation](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md)

Next up - [Directories Structure](./02_Directory_Structure.md)
