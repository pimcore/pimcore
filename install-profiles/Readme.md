
# How to use an install profile without installing it

This example uses the profile `demo-cms`, so for other profiles you may change the commands below accordingly. 


## Create the following symlinks
```
ln -sr app/config/parameters.example.yml app/config/parameters.yml
ln -sr install-profiles/demo-cms/app/Resources/views app/Resources/views
ln -sr install-profiles/demo-cms/src/AppBundle src/AppBundle
ln -sr install-profiles/demo-cms/web/var web/var 
ln -sr install-profiles/demo-cms/var/* var/
ln -sr install-profiles/demo-cms/web/static/ web/static
```

## Setup 
```
# copy & modify DB config
cp var/config/system.template.php var/config/system.php
 
# load structure and data
mysql -u root -p example < app/Resources/install/install.sql
mysql -u root -p example < install-profiles/demo-cms/dump/data.sql
```
