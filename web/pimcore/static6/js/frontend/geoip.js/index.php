<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

// referrer check
$referrerHost = "";
if (isset($_SERVER["HTTP_REFERER"])) {
    $referrerHost = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST);
}
if ($_SERVER["HTTP_HOST"] != $referrerHost) {
    die("Permission denied");
}

// this file doesn't boot the pimcore core for performance reasons
ini_set("display_errors", "Off");

require_once("../../../../../../vendor/autoload.php");



use GeoIp2\Database\Reader;

$geoDbFile = realpath("../../../../../../var/config/GeoLite2-City.mmdb");
$exception = "";
$record = null;

if (file_exists($geoDbFile)) {
    try {
        $reader = new Reader($geoDbFile);

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (!ip_is_private($ip)) {
            $record = $reader->city($ip);
        } else {
            throw new \Exception("You are using a private IP address, the GeoIP service can only operate with public IP addresses");
        }
    } catch (\Exception $e) {
        $exception = $e->getMessage();
    }
} else {
    throw new \Exception("GeoIP database doesn't exist. Please run the maintenance command to download the latest database.");
}

/* SOME FUNCTIONS */
/**
 * Check if ip is from an private network
 *
 * @param $ip
 * @return bool
 */
function ip_is_private($ip)
{
    $pri_addrs = [
        '10.0.0.0|10.255.255.255', // single class A network
        '172.16.0.0|172.31.255.255', // 16 contiguous class B network
        '192.168.0.0|192.168.255.255', // 256 contiguous class C network
        '169.254.0.0|169.254.255.255', // Link-local address also refered to as Automatic Private IP Addressing
        '127.0.0.0|127.255.255.255' // localhost
    ];

    $long_ip = ip2long($ip);
    if ($long_ip != -1) {
        foreach ($pri_addrs as $pri_addr) {
            list($start, $end) = explode('|', $pri_addr);

            // IF IS PRIVATE
            if ($long_ip >= ip2long($start) && $long_ip <= ip2long($end)) {
                return true;
            }
        }
    }

    return false;
}


/* OUTPUT */

header("Content-Type: text/javascript");

$lifetime = 86400 * 365 * 2; // 2 years lifetime
header("Cache-Control: public, max-age=" . $lifetime);
header("Expires: ". date("D, d M Y H:i:s T", time()+$lifetime));

?>

var pimcore = pimcore || {};
pimcore["location"] = {
<?php if ($record) {
    ?>
    ip: "<?= $ip ?>",
    latitude: <?= $record->location->latitude ?>,
    longitude: <?= $record->location->longitude ?>,
    country: {
        code: "<?= $record->country->isoCode ?>",
        name: "<?= $record->country->name ?>",
        names: <?= json_encode($record->country->names) ?>,
        subDivision: "<?= $record->mostSpecificSubdivision->name ?>"
    },
    address: {
        postalCode: "<?= $record->postal->code ?>",
        city: "<?= $record->city->name ?>"
    }
<?php

} else {
    ?>
    error: "<?= $exception ?>"
<?php

} ?>
};
