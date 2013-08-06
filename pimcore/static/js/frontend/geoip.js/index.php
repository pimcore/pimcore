<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

// this file doesn't boot the pimcore core for performance reasons

ini_set("display_errors", "On");
set_include_path(realpath("../../../../lib") . PATH_SEPARATOR);

spl_autoload_register(function ($class) {
    include_once(str_replace("\\","/",$class).".php");
});

use GeoIp2\Database\Reader;

$geoDbFile = realpath("../../../../../website/var/config/GeoLite2-City.mmdb");

if(file_exists($geoDbFile)) {
    $reader = new Reader($geoDbFile);
    $record = $reader->city('89.26.34.65');
}

header("Content-Type: text/javascript");

?>

var pimcore = pimcore || {};
pimcore["location"] = {
    latitude: <?= $record->location->latitude ?>,
    longitude: <?= $record->location->longitude ?>,
    country: {
        code: "<?= $record->country->isoCode ?>,
        name: <?= $record->country->name ?>,
        names: <?= json_encode($record->country->names) ?>,
        subDivision: <?= $record->mostSpecificSubdivision->name ?>
    },
    address: {
        postalCode: "<?= $record->postal->code ?>"
        city: "<?= $record->city->name ?>"
    }
};
