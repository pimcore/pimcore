# Constants File

Pimcore uses several constants for locating certain directories like logging, asses, versions etc. These constants are
defined in [`/pimcore/config/startup.php`](https://github.com/pimcore/pimcore/blob/pimcore4/pimcore/config/startup.php). 
 
If you need to overwrite these constants, e.g. for using a special directory for assets or versions at an object storage
at AWS S3, you can do so by creating file `constants.php` in document root of Pimcore.

An example file `constants.example.php` is shipped with Pimcore installation and could look like: 

```php
<?php

// to use this file you have to rename it to constants.php
// you can use this file to overwrite the constants defined in /pimcore/config/startup.php

define("PIMCORE_ASSET_DIRECTORY", "/custom/path/to/assets");
define("PIMCORE_TEMPORARY_DIRECTORY", "/my/tmp/path");

```

