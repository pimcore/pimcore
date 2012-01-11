<?php

$list = new Object_Objectbrick_Definition_List();
$list = $list->load();
foreach ($list as $fc) {
    $fc->save();
}


