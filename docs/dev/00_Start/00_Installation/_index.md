# Pimcore installation

## Available packages
You have to choose one of three packages before you start the installation process.
You can download every of these on [Pimcore download page](https://www.pimcore.org/en/resources/download). 

### Boilerplate installation 
This package contains sample data the same as our 
[online demo version](http://demo.pimcore.org). 

### Professional package 
Package without any data, just core Pimcore platform. 
Good choice when you're starting a new project.

### Nightly build
Daily released version. Shouldn't be used in the production environment.

## System requirements
You can visit a dedicated page to see the full list of system requirements: [System Requirements](!Start/Installation/System_Requirements)
Below the most important of those. 

[comment]: # (TODO: specified requirements)

## Installation process

The easiest way to install Pimcore is from your terminal.
There are two ways:

## Install via [Composer]('https://getcomposer.org/download/'):

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

## Install from package:

```bash
cd /your/document/root
wget https://www.pimcore.org/download/pimcore-latest.zip
# OR curl -O https://www.pimcore.org/download/pimcore-latest.zip
unzip pimcore-latest.zip
```

### Before installation 

You can choose between Apache2 and Nginx webserver.
Every of those has different configuration process. 
Please visit link from a table below with webserver you're going to use.

[comment]: # (TODO: Discuss and Update)

| Choice                                             | Version |
|----------------------------------------------------|---------|
| [Apache2](!Start/Installation/Apache_Configuration)| >=2.2   |
| [Nginx](!Start/Installation/Nginx_Configuration)   | ?       |


You also need to create a mysql database which is needed in the installation process.

Now go into your server url, you should see Pimcore install page. 
If you see warning like below, you should change 'website/var' directory permissions:

![Installation permission warning](/img/Installation_index_1.png)

If everything goes well you should see form like below:
 
![Installation success](/img/Installation_success.png)

[Have a look at directories structure](!Start/Directories_Structure)



