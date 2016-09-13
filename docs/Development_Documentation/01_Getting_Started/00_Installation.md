# Pimcore installation

The following guide assumes your're using a typical LAMP environment, if you're using a different setup (eg. Nginx) or facing a problem, please visit the [Installation Guide](../13_Installation_and_Upgrade/05_Installation_Guide.md) section.

## 1. Install Pimcore sources

The easiest way to install Pimcore is from your terminal.

```bash
cd /your/document/root
wget https://www.pimcore.org/download/pimcore-latest.zip
unzip pimcore-latest.zip
```

Keep in mind, that Pimcore needs to be installed into document root of your web server. 


## 2. Create database
```bash
mysql -u root -p -e "CREATE DATABASE pimcore charset=utf8;"
```

## 3. Launch Pimcore and finish installation
Finish the Pimcore installation by accessing the URL (eg. `https://your-host.com/`) in your web browser. 


## 4. Additional information
If you would like to know more about installation process or if you are having problems getting Pimcore up an running, visit 
the [Installation guide](../13_Installation_and_Upgrade/05_Installation_Guide.md) section.


Next up - [Directories structure](./02_Directories_Structure.md)