<?php

$list = new Object_Fieldcollection_Definition_List();
$list = $list->load();
foreach ($list as $fc) {
    $fc->save();
}
