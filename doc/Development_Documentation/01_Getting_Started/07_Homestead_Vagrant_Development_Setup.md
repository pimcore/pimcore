# Homestead/Vagrant Development Setup

To avoid having to develop on your local machine (and having to install a lot of dependencies on your machine) or on a remote
server, you can use [Vagrant](https://www.vagrantup.com/) to build a virtualized development environment. [Homestead](https://laravel.com/docs/master/homestead)
provides a simple configuration based Vagrant setup which can be used to quickly provision development environments. Your
code/project will be mounted to the virtual environment which means you can develop locally without having to take care
of syncing any changes to the virtual machine.

First of all, please read the installation docs for Homestead and make sure vagrant and a virtualization platform as VirtualBox
is properly installed. 

Assuming we work in a `~/work` directory on a *nix host (see Homestead docs for Windows details) we clone both the Pimcore
and the Homestead repository and set up Homestead to serve Pimcore. Please refer to the Homestead documentation regarding
the latest Homestead version.

```
$ mkdir ~/work
$ cd ~/work
$ git clone https://github.com/laravel/homestead.git
$ git clone https://github.com/pimcore/pimcore.git
$ cd homestead
$ git checkout 6.1.0 # Pimcore support was added in 6.1.0 - can be anything after this version 
$ bash init.sh
```

## Configure Homestead

This bootstraps the homestead installation. The `init.sh` creates 2 files `Homestead.yaml` and
`after.sh` which can be used to customize the setup. Let's map the pimcore checkout to a host 
in homestead by editing `Homestead.yaml`:

```yaml
---
ip: "192.168.85.15" # IP can be anything, but make sure it doesn't interfere with other interfaces
memory: 2048
cpus: 1
provider: virtualbox
name: homestead
mariadb: true

authorize: ~/.ssh/id_rsa.pub
keys:
    - ~/.ssh/id_rsa

folders:
    - map: ~/work/pimcore
      to: /home/vagrant/pimcore

sites:
    - map: pimcore.app
      to: /home/vagrant/pimcore/web
      type: pimcore

databases:
    - pimcore
```

Make sure to point the document root of the `site` to the `web` directory of your installation. The entry `type: pimcore`
configures Homestead to set up an Apache instance with a configuration which is working with Pimcore.


## Updating `/etc/hosts`

To have your machine automatically resolve the host `pimcore.app` you can install the `vagrant-hostsupdater` plugin
which takes care of mapping Vagrant IPs to hostnames by editing your `/etc/hosts` file:

```
$ vagrant plugin install vagrant-hostsupdater
```

If you can't or don't want to install the plugin you need to update your `/etc/hosts` file manually.

## Installing/Provisioning Pimcore

The second file mentioned above is the `after.sh` which you can use to add custom provisioning
logic. You can use the following template to run the CLI installer for all your desired installations. This
will be executed during provisioning (first start of a virtual machine or when called with `--provision`). 

```#!/bin/sh
set -e

# installs dependencies
install_dependencies() {
  cd ~/$1

  # PHP extensions
  sudo apt-get install -y php7.1-apcu php7.1-imagick php7.1-redis

  # System packages
  sudo apt-get install -y libreoffice libreoffice-script-provider-python libreoffice-math xfonts-75dpi poppler-utils inkscape libxrender1 libfontconfig1 ghostscript libimage-exiftool-perl

  # ffmpeg
  if [ ! -e /usr/local/ffmpeg/ffmpeg ]; then
    wget https://johnvansickle.com/ffmpeg/builds/ffmpeg-git-64bit-static.tar.xz -O ffmpeg.tar.xz
    tar -Jxf ffmpeg*.tar.xz
    rm ffmpeg*.tar.xz
    sudo mv ffmpeg-* /usr/local/ffmpeg
    sudo ln -s /usr/local/ffmpeg/ffmpeg /usr/local/bin/
    sudo ln -s /usr/local/ffmpeg/ffprobe /usr/local/bin/
    sudo ln -s /usr/local/ffmpeg/qt-faststart /usr/local/bin/
    sudo ln -s /usr/local/ffmpeg/qt-faststart /usr/local/bin/qtfaststart
  fi

  # Image optimizers
  if [ ! -e /usr/local/bin/zopflipng ]; then
    sudo wget https://github.com/imagemin/zopflipng-bin/raw/master/vendor/linux/zopflipng -O /usr/local/bin/zopflipng
    sudo chmod 0755 /usr/local/bin/zopflipng
  fi
  if [ ! -e /usr/local/bin/pngcrush ]; then
    sudo wget https://github.com/imagemin/pngcrush-bin/raw/master/vendor/linux/pngcrush -O /usr/local/bin/pngcrush
    sudo chmod 0755 /usr/local/bin/pngcrush
  fi
  if [ ! -e /usr/local/bin/jpegoptim ]; then
    sudo wget https://github.com/imagemin/jpegoptim-bin/raw/master/vendor/linux/jpegoptim -O /usr/local/bin/jpegoptim
    sudo chmod 0755 /usr/local/bin/jpegoptim
  fi
  if [ ! -e /usr/local/bin/pngout ]; then
    sudo wget https://github.com/imagemin/pngout-bin/raw/master/vendor/linux/x64/pngout -O /usr/local/bin/pngout
    sudo chmod 0755 /usr/local/bin/pngout
  fi
  if [ ! -e /usr/local/bin/advpng ]; then
    sudo wget https://github.com/imagemin/advpng-bin/raw/master/vendor/linux/advpng -O /usr/local/bin/advpng
    sudo chmod 0755 /usr/local/bin/advpng
  fi
  if [ ! -e /usr/local/bin/cjpeg ]; then
    sudo wget https://github.com/imagemin/mozjpeg-bin/raw/master/vendor/linux/cjpeg -O /usr/local/bin/cjpeg
    sudo chmod 0755 /usr/local/bin/cjpeg
  fi
}

# installs pimcore
# expects the following
#   - first argument is the site and DB name (site and DB name must match)
#   - second argument is the install profile to use (defaults to empty)
#   - site is mapped to /home/vagrant/<site name>
install_pimcore() {
    cd ~/$1
    
    # install composer dependencies
    composer install

    # Pimcore was already installled - skip installation
    if [ -e var/config/system.php ]; then
        >&2 echo "var/config/system.php was found in $1...skipping installation"
        return
    fi

    # prepare DB - change character set to utf8mb4
    mysql -e "ALTER DATABASE \`$1\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

    # install pimcore
    bin/install --symlink --profile ${2:-empty} --no-interaction \
        --admin-username admin --admin-password admin \
        --mysql-username homestead --mysql-password secret --mysql-database $1
}

# install dependencies
install_dependencies

# install pimcore
export PIMCORE_ENVIRONMENT=dev
install_pimcore pimcore demo-basic-twig

# install further instances
# install_pimcore pimcore-test basic-cms
# ...

```

## Starting the machine

As you have everything set up, you can try to start your development environment:

```
$ cd ~/work/homestead
$ vagrant up
```

This will take a couple of minutes on the first start as it needs to download, import and provision
your machine. If everything went well you should be able to open [https://pimcore.app](https://pimcore.app)
in your browser and see a working Pimcore installation.

## Enabling NFS for better performance
You can try enabling NFS for better shared folder performance.

### Linux

Install nfs-kernel-server on your host machine. For Ubuntu:

```
$ sudo apt-get install nfs-kernel-server
```

Add nfs to folders in Homestead.yaml:
```
folders:
    - map: ~/work/pimcore
      to: /home/vagrant/pimcore
      type: "nfs"
      options:
        linux__nfs_options: ['async','rw','no_subtree_check','all_squash']
```

Reload Vagrant to check if Pimcore speeds up:
```
$ vagrant reload
```

### Windows
Install winnfsd plugin for Vagrant:
```
$ vagrant plugin install vagrant-winnfsd
```

Add nfs to folders in Homestead.yaml:
```
folders:
    - map: ~/work/pimcore
      to: /home/vagrant/pimcore
      type: "nfs"
```
Reload Vagrant to check if Pimcore speeds up:
```
$ vagrant reload
```

## Troubleshooting

### Error `Warning: SessionHandler::read(): Session data file is not created by your uid`

This happens as vagrant mounts the whole Pimcore directory including the session storage in `var/sessions`. As the uid of
created files in this directory is not the same as the webserver user's one, the session storage complains. To circumvent
this problem, you can reconfigure the session storage to be in a location on the virtual machine with the following setting.

```yaml
framework:
    session:
        # http://symfony.com/doc/current/session/sessions_directory.html
        save_path: /tmp/pimcore/var/sessions"
```

As this config setting is only valid for your local environment, you should add this to a config file which is not tracked
by VCS to avoid deploying the setting to other installations. Config files in `app/config/local` will be automatically loaded
and are excluded in Pimcore's default `.gitignore`. For example, you can add the setting above in a file `app/config/local/session_save_path.yml`.
