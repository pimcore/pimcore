# Pimcore installation

## 1. Install Pimcore sources on file system

The easiest way to install Pimcore is from your terminal.
There are two ways:

### Install via [Composer](https://getcomposer.org/download/):

```bash
cd /your/working/directory
composer create-project pimcore/pimcore ./your-project-name
cd your-project-name
composer dumpautoload -o
```

To install specific release or the nightly build you can use:

```bash
composer create-project -s dev pimcore/pimcore ./your-project-name dev-master
```

### Install from package:

```bash
cd /your/document/root
wget https://www.pimcore.org/download/pimcore-latest.zip
# OR curl -O https://www.pimcore.org/download/pimcore-latest.zip
unzip pimcore-latest.zip
```

Keep in mind, that Pimcore needs to be installed into Document Root of your web browser. 


## 2. Create Database
Create a database for Pimcore (charset: utf8). 
```bash
mysql -u root -p -e "CREATE DATABASE pimcore charset=utf8;"
```

## 3. Launch Pimcore and finish installation
Finish the Pimcore installation by accessing the URL of your web browser. There you need to configure database access and 
 admin user account settings and finish the installation of Pimcore. 




If you would like to know more about installation process visit 
the [Installation Guide](../13_Installation_and_Upgrade/05_Installation_Guide.md) section.


Next up - [Directories Structure](./02_Directories_Structure.md)