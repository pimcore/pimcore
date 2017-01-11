# Pimcore Installation

The following guide assumes your're using a typical LAMP environment, if you're using a different setup (eg. Nginx) or facing a problem, please visit the [Installation Guide](../13_Installation_and_Upgrade/README.md) section.

## 1. System Requirements
Please have a look at [System Requirements](../13_Installation_and_Upgrade/01_System_Requirements.md) and ensure your system is ready for Pimcore. 

## 2. Install Pimcore Sources
The easiest way to install Pimcore is from your terminal using our installer package. 
We additionally provide a [Composer based install guide](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/04_Composer_Install.md) and of course you can install Pimcore also without the help of the command line using your favorite tools (FTP, ...).

Change into the document root folder of your new project: 
```bash
cd /your/document/root
```

Pimcore is offering [3 installation packages](https://www.pimcore.org/download) for different use-cases: 

|  |  |
|--------------------------|-----------------------------------------------------------------------------------------------------------------|
| **Quick Start Bundle**   | Boilerplate - **Recommended for beginners** This package contains the core including sample data of our [online demo version](http://demo.pimcore.org).  |
| **Professional Package** | Package without any data, just the core Pimcore platform. Good choice when you're starting a new project as an experienced Pimcore developer.           |
| **Nightly Build**        | Daily released version. Shouldn't be used in production.                                        |
  
  
#### Choose & Download a Package: 
##### Professional Package
```bash
wget https://www.pimcore.org/download/pimcore-latest.zip -O pimcore-install.zip
```

##### Quick Start Bundle
```bash
wget https://www.pimcore.org/download/pimcore-data.zip -O pimcore-install.zip
```

Unzip the installer package into the current folder (document root): 
```bash
unzip pimcore-install.zip
```

Keep in mind, that Pimcore needs to be installed into the **document root** of your web server. Specific configurations and optimizations for your webserver are available here: [Apache](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md), [Nginx](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md)

Pimcore requires write access to the following directories: `/website/var` and `/pimcore` ([Details](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/03_File_Permissions.md)) 


> No CLI? Click [Professional Package](https://www.pimcore.org/download/pimcore-latest.zip) or [Quick Start Bundle](https://www.pimcore.org/download/pimcore-data.zip) to download the package in your browser and extract/upload Pimcore manually on your server (document root). 


## 3. Create Database
```bash
mysql -u root -p -e "CREATE DATABASE pimcore charset=utf8mb4;"
```

For further information please visit out [DB Setup Guide](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/05_DB_Setup.md)

## 4. Launch Pimcore and Finish Installation
Finish the Pimcore installation by accessing the URL (eg. `https://your-host.com/`) in your web browser. 
1. Fill in the required fields (database + admin user)
2. Press ***Check Requirements*** to check if your system is ready for Pimcore
3. Click ***Install Now!*** 


## 5. Maintenance Cron Job
```text
*/5 * * * * php /path/to/pimcore/cli/console.php maintenance
```
Keep in mind, that the cron job has to run as the same user as the web interface (eg. `www-data`).

## 6. Additional Information & Help
If you would like to know more about installation process or if you are having problems getting Pimcore up and running, visit 
the [Installation Guide](../13_Installation_and_Upgrade/README.md) section.

## 7. Further Reading
- [Apache Configuration](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/01_Apache_Configuration.md)
- [Nginx Configuration](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/03_Nginx_Configuration.md)
- [Database Setup](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/05_DB_Setup.md)
- [Additional Tools Installation](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md)

Next up - [Directories Structure](./02_Directories_Structure.md)