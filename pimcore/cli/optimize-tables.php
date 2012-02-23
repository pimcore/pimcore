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

include_once("startup.php");

$db = Pimcore_Resource::get();
$tables = $db->fetchAll("SHOW TABLES");

foreach ($tables as $table) {
    $t = current($table);
    try {
        $db->query("OPTIMIZE TABLE " . $t);
    } catch (Exception $e) {
        Logger::error($e);
    }
}
