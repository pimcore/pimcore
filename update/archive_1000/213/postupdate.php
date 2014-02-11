<?php

@ini_set("memory_limit", "-1");
@ini_set("max_execution_time", "360");

// get db connection
$db = Pimcore_Resource_Mysql::get("database");


$classList = new Object_Class_List();
$classes = $classList->load();

foreach ($classes as $class) {
    
    $db->getConnection()->exec("RENAME TABLE `object_data_".$class->getId()."` TO `object_query_".$class->getId()."`;");
    
    Logger::log("Update 213: Start Class " . $class->getId());
    echo "<br />Update Class: " . $class->getName()."(".$class->getId().")<br />";
    $class->save();
    
    //@REMOVE cleanup
    //$db->getConnection()->exec("TRUNCATE `object_relations_".$class->getId()."`;");
    //$db->getConnection()->exec("TRUNCATE `object_store_".$class->getId()."`;");
    
    // get objects for classes
    $objects = $db->fetchAll("SELECT o_id FROM objects WHERE o_classId = ?",$class->getId());
    
    $classFields = $class->getFieldDefinitions();
    
    foreach ($objects as $object) {
        // get data values
        $fields = $db->fetchAll("SELECT * FROM objects_data WHERE o_id = ?",$object["o_id"]);
        
        $insert = array("oo_id" => $object["o_id"]);
        
        foreach ($fields as $field) {
            if($field["type"] == "multihref") {
                $od = @unserialize($field["data"]);
                
                if(!empty($od)) {
                    foreach ($od as $entry) {
                        if(!empty($entry)) {
                            try {
                                $db->insert("object_relations_".$class->getId(), array(
                                    "src_id" => $object["o_id"],
                                    "dest_id" => $entry["id"],
                                    "type" => $entry["type"],
                                    "fieldname" => $field["name"]
                                ));
                            }
                            catch (Exception $e) {
                                echo "WARNING: " . $e->getMessage()."<br />";
                                Logger::log("Update 213: " . $e->getMessage());
                            }
                        }    
                    }
                }
                unset($field["data"]);
            }
            else if($field["type"] == "href") {
                $od = @unserialize($field["data"]);
                if(!empty($od)) {
                    try {
                        $db->insert("object_relations_".$class->getId(), array(
                            "src_id" => $object["o_id"],
                            "dest_id" => $od["id"],
                            "type" => $od["type"],
                            "fieldname" => $field["name"]
                        ));
                    }
                    catch (Exception $e) {
                        echo "WARNING: " . $e->getMessage()."<br />";
                        Logger::log("Update 213: " . $e->getMessage());
                    }
                }
                unset($field["data"]);
            }
            else if($field["type"] == "objects") {
                $od = @unserialize($field["data"]);
                if(!empty($od)) {
                    foreach ($od as $entry) {
                        if(!empty($entry)) {
                            try {
                                $db->insert("object_relations_".$class->getId(), array(
                                    "src_id" => $object["o_id"],
                                    "dest_id" => $entry["id"],
                                    "type" => "object",
                                    "fieldname" => $field["name"]
                                ));
                            }
                            catch (Exception $e) {
                                echo "WARNING: " . $e->getMessage()."<br />";
                                Logger::log("Update 213: " . $e->getMessage());
                            }
                            
                        }    
                    }
                }
                unset($field["data"]);
            }
            
            if($field["data"] && $classFields[$field["name"]]) {
                $insert[$field["name"]] = $field["data"];
            }
        } 
        $db->insert("object_store_".$class->getId(), $insert);
    }
}


Logger::log("Update 213: Rename objects_data");
$db->getConnection()->exec("RENAME TABLE `objects_data` TO `deprecated____objects_data`;");

// empty document cache
Pimcore_Model_Cache::clearAll();

// empty cache directory
$files = scandir(PIMCORE_CACHE_DIRECTORY);
foreach ($files as $file) {
	if(is_file(PIMCORE_CACHE_DIRECTORY."/".$file)) {
		unlink(PIMCORE_CACHE_DIRECTORY."/".$file);
	}
}


Logger::log("Update 213: Update completed successfully");
echo "<br /><br />Update completed successfully in ". (time()-$start) . " seconds";

?>