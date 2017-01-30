<?php

if (!@constant("PDO::MYSQL_ATTR_FOUND_ROWS")) {
    $message = '<b>Pimcore requires <a href="http://php.net/pdo-mysql" target="_blank">pdo-mysql</a>.</b><br>' .
        'Please install this PHP extension to continue the upgrade. <br>' .
        'On Debian based systems this can be done by: <code>apt-get install php-pdo-mysql</code><br>' .
        'After installing the required extension, just start the update process again.';

    \Pimcore\Logger::crit(strip_tags($message));

    echo "<b>Action needed!</b><br><br>";
    echo $message;
    exit;
}
