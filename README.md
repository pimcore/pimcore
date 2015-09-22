# pimcore

THE PREMIER OPEN-SOURCE CMS/CMF, PIM, DAM, ECOMMERCE-SUITE

[![Software License](https://img.shields.io/badge/license-BSD-brightgreen.svg?style=flat)](LICENSE.txt)
[![Current Release](https://img.shields.io/packagist/v/pimcore/pimcore.svg?style=flat)](https://packagist.org/packages/pimcore/pimcore)
[![Build Status](https://travis-ci.org/pimcore/pimcore.svg?branch=master)](https://travis-ci.org/pimcore/pimcore)
[![HHVM Status](https://img.shields.io/hhvm/pimcore/pimcore.svg)](https://travis-ci.org/pimcore/pimcore)
[![Gitter](https://img.shields.io/badge/gitter-join%20chat-brightgreen.svg?style=flat)](https://gitter.im/pimcore/pimcore?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)


![Editing Interface](website_demo/static/screenshots/1.png)

* Homepage: [http://www.pimcore.org/](http://www.pimcore.org/) - Learn more about pimcore
* Twitter: [@pimcore](https://twitter.com/pimcore) - Get the latest news
* Issue Tracker: - [Issues](https://github.com/pimcore/pimcore/issues) - Report bugs here
* Forums: - http://www.pimcore.org/board/ - Get help


## Getting started

Download the [latest release](http://www.pimcore.org/download) and extract the archive in document root.
Create a database for pimcore (charset: utf8). If you have a website_example (empty installation) or a website_demo (boilerplate) folder please rename one of them to website (only if cloning from git).
Run the pimcore installation by accessing the URL where you uploaded the pimcore files in a browser.

```
cd /your/document/root
wget https://www.pimcore.org/download/pimcore-latest.zip
unzip pimcore-latest.zip

mysql -u root -p -e "CREATE DATABASE pimcore charset=utf8;"

# now launch http://yourhostname.tld/install
```

[A detailed installation guide can be found here.](http://www.pimcore.org/wiki/pages/viewpage.action?pageId=12124463)


## Copyright and license

Copyright: [pimcore](http://www.pimcore.org) GmbH  
For licensing details please visit [LICENSE.md](LICENSE.md) 
