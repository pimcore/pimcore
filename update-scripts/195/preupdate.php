<?php

$incompatible = false;

$db = \Pimcore\Db::get();

$largePrefix = $db->fetchRow("SHOW GLOBAL VARIABLES LIKE 'innodb\_large\_prefix';");
if($largePrefix && $largePrefix['Value'] != 'ON') {
    $incompatible = true;
}
$fileFormat = $db->fetchRow("SHOW GLOBAL VARIABLES LIKE 'innodb\_file\_format';");
if($fileFormat && $fileFormat['Value'] != 'Barracuda') {
    $incompatible = true;
}

if ($incompatible) {
    echo '<b>Your MySQL/MariaDB Server is incompatible!</b><br />';
    echo 'Please ensure the following MySQL/MariaDB system variables are set accordingly: <br/>';
    echo '<pre>innodb_file_format = Barracuda
innodb_large_prefix = 1</pre>';
    exit;
}
