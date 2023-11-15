# Webserver Installation

The following guide assumes you're using a typical LAMP environment. If you're using a different setup (eg. Nginx) or you're facing a problem, please visit the [Installation Guide](../../23_Installation_and_Upgrade/README.md) section.

## 1. System Requirements

Please have a look at [System Requirements](../../23_Installation_and_Upgrade/01_System_Requirements.md) and ensure your system is ready for Pimcore.

## 2. Install Pimcore & Dependencies

The easiest way to install Pimcore is from your terminal using Composer.
Change into the root folder of your project:
  
```bash
cd /your/project
```

:::caution

Please remember: project root != document root

:::

### Choose a Package to Install
We offer 2 different installation packages: 
* a demo package with exemplary blueprints.
* an empty skeleton package for experienced developers.

##### 1. Skeleton Package (only for experienced Pimcore developers)
```bash
COMPOSER_MEMORY_LIMIT=-1 composer create-project pimcore/skeleton my-project
```

##### Demo Package
```bash
COMPOSER_MEMORY_LIMIT=-1 composer create-project pimcore/demo my-project
```

Point the document root of your vhost to the newly created `/public` folder (eg. `/your/project/public`).
Keep in mind that Pimcore needs to be installed **outside** of the **document root**.
Specific configurations and optimizations for your web server are available here:
[Apache](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md),
[Nginx](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md)

Pimcore requires write access to the following directories (relative to your project root): `/var`, `/public/var` ([Details](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/03_File_Permissions.md))

If you're running the installation using a [custom environment name](../../21_Deployment/03_Configuration_Environments.md), ensure you already have the right config files in place, e.g. `config/packages/[env_name]/config.yaml`. 

## 3. Create Database

```bash
mysql -u root -p -e "CREATE DATABASE project_database charset=utf8mb4;"
```

For further information please visit out [DB Setup Guide](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/05_DB_Setup.md)

## 4. Launch Installer

```
cd ./my-project
./vendor/bin/pimcore-install
```

This launches the interactive installer with a few questions. Make sure that you set the `memory_limit` to at least `512M` in your `php.ini` file.   

:::info

 Pimcore allows a fully automated installation process. Read more here: [Advanced Installation Topics](../02_Advanced_Installation_Topics/README.md)

:::

##### Open Admin Interface
After the installer has finished, you can open the admin interface: `https://your-host.com/admin`

##### Debugging Installation Issues

The installer writes a log in `var/log` which contains any errors encountered during the installation. Please have a look at the logs as a starting point when debugging installation issues.


## 5. Maintenance Cron Job

Maintenance tasks are handled with Symfony Messenger. The `pimcore:maintenance` command will add the maintenance
messages to the bus and run them afterward immediately from the queue. However, it is  recommended to set up independent workers that process the queues by running `bin/console messenger:consume pimcore_core pimcore_maintenance pimcore_image_optimize pimcore_asset_update` (using e.g.
`Supervisor`).

```bash
# this command needs to be executed via cron or similar task scheduler
# it fills the message queue with the necessary tasks, which are then processed by messenger:consume
*/5 * * * * /your/project/bin/console pimcore:maintenance

# it's recommended to run the following command using a process control system like Supervisor
# please follow the Symfony Messenger guide for a best practice production setup: 
# https://symfony.com/doc/current/messenger.html#deploying-to-production
*/5 * * * * /your/project/bin/console messenger:consume pimcore_core pimcore_maintenance pimcore_image_optimize pimcore_search_backend_message --time-limit=300
```

> Depending on installed and activated extensions, it might be necessary to add additional transports to the messenger consume command. Please look at the documentation of corresponding extensions for more details. 

Keep in mind that the cron job has to run as the same user as the web interface to avoid permission issues (eg. `www-data`).

For information about how to handle failed jobs, see this [section](../02_Advanced_Installation_Topics/01_Symfony_Messenger.md).

## Caching
Make sure to use any sort of [caching](https://pimcore.com/docs/platform/Pimcore/Development_Tools_and_Details/Cache/) to improve performance. We recommend Redis cache storage.

## 6. Additional Information & Help

If you would like to know more about the installation process or if you are having problems getting Pimcore up and running, visit the [Installation Guide](../../23_Installation_and_Upgrade/README.md) section.

## 7. Further Reading

- [Symfony Messenger](../02_Advanced_Installation_Topics/01_Symfony_Messenger.md)
- [Advanced Installation Topics](../02_Advanced_Installation_Topics/README.md)
- [Apache Configuration](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md)
- [Nginx Configuration](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md)
- [Database Setup](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/05_DB_Setup.md)
- [Additional Tools Installation](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md)

Next up - [Directories Structure](../03_Directory_Structure.md)
