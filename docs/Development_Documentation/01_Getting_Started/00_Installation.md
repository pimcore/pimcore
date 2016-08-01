# Pimcore installation

The easiest way to install Pimcore is from your terminal.
There are two ways:

## Install via [Composer](https://getcomposer.org/download/):

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



If you would like to know more about installation process visit 
the [Installation Guide](../13_Installation_and_Upgrade/05_Installation_Guide.md) section.

[Directories Structure](./02_Directories_Structure.md)