<?php


$classList = new Object_Class_List();
$classes=$classList->load();
foreach($classes as $c){
    $fd=$c->getFieldDefinitions();
    $changed = false;
    foreach ($fd as $key => $def) {
        if($def->fieldtype=="href" or $def->fieldtype=="multihref"){
            $def->setAssetsAllowed(true);
            $def->setObjectsAllowed(true);
            $def->setDocumentsAllowed(true);
            $changed =true;
        }
    }
    if($changed){
        $c->save();
    }

}

?>


<b>Release Notes (292):</b>
<br />
- Objects: check on types/classes of href, multihref and objects<br />


