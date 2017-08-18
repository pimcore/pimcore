
# How to use an install profile without installing it

This example uses the profile `demo-basic`, so for other profiles you may change the commands below accordingly. 


## Create the following symlinks
```
ln -sr app/config/parameters.example.yml app/config/parameters.yml
ln -sr install-profiles/demo-basic/app/Resources/views app/Resources/views
ln -sr install-profiles/demo-basic/app/config/local/* app/config/local/
ln -sr install-profiles/demo-basic/src/AppBundle src/AppBundle
ln -sr install-profiles/demo-basic/web/var web/var 
ln -sr install-profiles/demo-basic/var/* var/
ln -sr install-profiles/demo-basic/web/static/ web/static
```

## Setup 
```
# copy & modify DB config
cp var/config/system.template.php var/config/system.php
 
# load structure and data
mysql -u root -p example < pimcore/lib/Pimcore/Install/Resources/install.sql'
mysql -u root -p example < install-profiles/demo-basic/dump/data.sql
```
