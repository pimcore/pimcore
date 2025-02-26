# System Requirements

## Server Requirements 

For production, we highly recommend a *nix based system.

> Also have a look at our official [Docker images](https://hub.docker.com/r/pimcore/pimcore) and the
> docker-compose files in our [skeleton](https://github.com/pimcore/skeleton/blob/11.x/docker-compose.yaml) 
> and [demo application](https://github.com/pimcore/demo/blob/11.x/docker-compose.yaml).  


### Webserver 
- Apache >= 2.4
  - mod_rewrite
  - .htaccess support (`AllowOverride All`)
- Nginx


### PHP >=8.1 \<8.4
Both **mod_php** and **FCGI (FPM)** are supported.  

#### Required Settings and Modules & Extensions
- `memory_limit` >= 128M
- `upload_max_filesize` and `post_max_size` >= 100M (depending on your data) 
- [pdo_mysql](https://php.net/pdo-mysql)
- [iconv](https://php.net/iconv)
- [dom](https://php.net/dom)
- [simplexml](https://php.net/simplexml)
- [gd](https://php.net/gd)
- [exif](https://php.net/exif)
- [file_info](https://php.net/fileinfo) 
- [mbstring](https://php.net/mbstring)
- [zlib](https://php.net/zlib)
- [zip](https://php.net/zip)
- [intl](https://www.php.net/intl)
- [opcache](https://php.net/opcache)
- [curl](https://php.net/curl)
- CLI SAPI (for Cron Jobs)
- [Composer 2](https://getcomposer.org/) (added to `$PATH` - see also [Additional Tools Installation](./03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md))

#### Recommended or Optional Modules & Extensions 
- [imagick](https://php.net/imagick) (if not installed *gd* is used instead, but with less supported image types)
  - LCMS delegate for Image Magick to prevent negative colors for CMYK images
- [phpredis](https://github.com/phpredis/phpredis) (recommended cache backend adapter)
- [graphviz](https://www.graphviz.org/) (for rendering workflow overview)
- [mysqli](https://php.net/mysqli) (PDO although is still required for parameter mappings)
  
### Database Server
- MariaDB >= 10.3
- MySQL >= 8.0
- Percona Server (supported versions see MySQL)
- [AWS Aurora](https://aws.amazon.com/de/about-aws/whats-new/2021/11/amazon-aurora-mysql-8-0/) (supported versions see MySQL)

#### Features
- InnoDB / XtraDB storage engine
- Support for InnoDB fulltext indexes

#### Permissions
All permissions on database level, specifically: 
- Select, Insert, Update, Delete table data
- Create tables
- Drop tables
- Alter tables
- Manage indexes
- Create temp-tables
- Lock tables
- Execute
- Create view
- Show view

> For installing our [demo](https://github.com/pimcore/demo) additionally `Create routine` and `Alter routine` are needed. 

#### System Variables
```
[mysqld]
innodb_file_per_table = 1

[mariadb]
plugin_load_add = ha_archive # optional but recommended, starting from mariadb 10.1 archive format is no more activated by default (check and adapt for mysql or other database software)
```

### Redis (optional but recommended for caching)
All versions > 3 are supported
##### Configuration 
```
# select an appropriate value for your data
maxmemory 768mb
                   
# IMPORTANT! Other policies will cause random inconsistencies of your data!
maxmemory-policy volatile-lru   
save ""
```

### RabbitMQ (optional but recommended for messenger)
Pimcore utilizes the Symfony Messenger for various background processes to ensure efficient handling of tasks. As the number of Pimcore elements grows, the need for a more robust and scalable queue system becomes evident. RabbitMQ is recommended in these scenarios for its exemplary performance and scalability. It excels in managing high volumes of messages and complex workflows, making it the preferred choice for systems that demand reliability and efficiency at scale.

Please follow [Symfony Messenger](../01_Getting_Started/02_Advanced_Installation_Topics/01_Symfony_Messenger.md) for more information about pimcore messenger. For an example configuration, refer to [this link](https://github.com/pimcore/skeleton/blob/11.x/.docker/messenger.yaml).

### Operating System
Please ensure you have installed all required packages to ensure proper locale support by PHP.
On Debian based systems, you can use the following command to install all required packages: 
`apt-get install locales-all` (on some systems there may be a reboot required).

### Additional Server Software 
- FFMPEG (>= 3)
- Ghostscript (>= 9.16)
- LibreOffice (>= 4.3)
- Chromium/Chrome
- xvfb
- timeout (GNU core utils)
- pdftotext (poppler utils)
- inkscape
- pngquant
- optipng
- jpegoptim
- exiftool
- [Graphviz](https://www.graphviz.org/)

Please visit [Additional Tools Installation](03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md) for additional information. 

## Browser Requirements
Pimcore supports always the latest 2 versions of all 4 major desktop browsers at the time of a release. 

- **Google Chrome  (Recommended)**
- Mozilla Firefox 
- Microsoft Edge
- Apple Safari

*Note:* Microsoft Internet Explorer 11 won`t be supported from Pimcore 6.0.0 or higher. More details **[here](https://github.com/pimcore/pimcore/issues/2989)**.

### System Requirements Check

A tool that gives you an overview of required and optional system requirements for running Pimcore Application.

You can check via Admin UI `Tools` / `System Info & Tools` / `System-Requirements Check` menu.

Or via following CLI command:

```bash
bin/console pimcore:system:requirements:check
```
