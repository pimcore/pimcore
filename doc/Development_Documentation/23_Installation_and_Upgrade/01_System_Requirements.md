## Server Requirements 

For production we highly recommend a *nix based system. 

### Webserver 
- Apache >= 2.2
  - mod_rewrite
  - .htaccess support (`AllowOverride All`)
- Nginx


### PHP >= 7.2
Both **mod_php** and **FCGI (FPM)** are supported.  

#### Required Settings and Modules & Extensions
- `memory_limit` >= 128M
- `upload_max_filesize` and `post_max_size` >= 100M (depending on your data) 
- [pdo_mysql](http://php.net/pdo-mysql) or [mysqli](http://php.net/mysqli)
- [iconv](http://php.net/iconv)
- [dom](http://php.net/dom)
- [simplexml](http://php.net/simplexml)
- [gd](http://php.net/gd)
- [exif](http://php.net/exif)
- [file_info](http://php.net/fileinfo) 
- [mbstring](http://php.net/mbstring)
- [zlib](http://php.net/zlib)
- [zip](http://php.net/zip)
- [intl](http://www.php.net/intl)
- [opcache](http://php.net/opcache)
- [curl](http://php.net/curl)
- CLI SAPI (for Cron Jobs)
- [Composer](https://getcomposer.org/) (added to `$PATH` - see also [Additional Tools Installation](./03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md))

#### Recommended Modules & Extensions 
- [imagick](http://php.net/imagick) (if not installed *gd* is used instead, but with less supported image types)
- [phpredis](https://github.com/phpredis/phpredis) (recommended cache backend adapter)
- [graphviz](https://www.graphviz.org/) (for rendering workflow overview)

### Database Server
- MariaDB >= 10.0.0.5
- MySQL >= 5.6.4 
- AWS Aurora (MySQL)
- Percona Server

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
innodb_file_format = Barracuda
innodb_large_prefix = 1
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

### Operating System
Please ensure you have installed all required packages to ensure proper locale support by PHP.
On Debian based systems, you can use the following command to install all required packages: 
`apt-get install locales-all` (on some systems there may be a reboot required).


### Additional Server Software 
- FFMPEG (>= 3)
- Ghostscript (>= 9.16)
- LibreOffice (>= 4.3)
- wkhtmltoimage / wkhtmltopdf (>= 0.12)
- xvfb
- timeout (GNU core utils)
- pdftotext (poppler utils)
- inkscape
- zopflipng
- pngcrush
- jpegoptim
- pngout 
- advpng
- cjpeg ([MozJPEG](https://github.com/mozilla/mozjpeg))
- exiftool
- SQIP - SVG Placeholder
- [facedetect](https://github.com/wavexx/facedetect) 
- [Graphviz](https://www.graphviz.org/)

Please visit [Additional Tools Installation](03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md) for additional information. 

## Browser Requirements
Pimcore supports always the latest 2 versions of all 4 major desktop browsers at the time of a release. 

- **Google Chrome  (Recommended)**
- Mozilla Firefox 
- Microsoft Edge
- Apple Safari

*Note:* Microsoft Internet Explorer 11 won`t be supported from Pimcore 6.0.0 or higher. More details **[here](https://github.com/pimcore/pimcore/issues/2989)**.