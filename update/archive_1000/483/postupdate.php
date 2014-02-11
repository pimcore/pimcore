<?php


// get db connection
$db = Pimcore_Resource_Mysql::get();

// get classes
$classList = new Object_Class_List();
$classes=$classList->load();


foreach($classes as $c){
    try {
        $l = $db->fetchRow("SELECT layoutDefinitions FROM classes WHERE id = '".$c->getId()."'");
        
        $c->setLayoutDefinitions(unserialize($l["layoutDefinitions"]));
        $c->save();
    }
    catch (Exception $e) {
        
        $defFileTmp = PIMCORE_TEMPORARY_DIRECTORY . "/class_dump_update_483_id_". $c->getId() . ".txt";
        file_put_contents($defFileTmp, $l["layoutDefinitions"]);
        
        echo "Can't update class: " . $c->getName() . "with ID: " . $c->getId()."<br />See Debug-Log for details<br /><br />";
        logger::log($e);
    }
}


$db->getConnection()->exec("ALTER TABLE `classes` DROP COLUMN `layoutDefinitions`;");


?>