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
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Element_Service_Resource extends Pimcore_Model_Resource_Abstract {
    
    public function cleanupBrokenViews () {

        $tables = $this->db->fetchAll("SHOW FULL TABLES");
        foreach ($tables as $table) {

            reset($table);
            $name = current($table);
            $type = next($table);

            if ($type == "VIEW") {
                try {
                    Logger::debug("SHOW CREATE VIEW " . $name);
                    $createStatement = $this->db->fetchRow("SHOW CREATE VIEW " . $name);
                } catch (Exception $e) {
                    if(strpos($e->getMessage(), "references invalid table") !== false) {
                        Logger::err("view " . $name . " seems to be a broken one, it will be removed");
                        Logger::err("error message was: " . $e->getMessage());

                        $this->db->query("DROP VIEW " . $name);
                    } else {
                        Logger::error($e);
                    }
                }
            }
        }
    }
}
