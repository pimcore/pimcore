<?php

// we need to use the old class names here
// absolutely no clue why, but it works ;-)

// get db connection
$db = Pimcore_Resource::get();
$db->query("ALTER TABLE `email_log`
CHANGE `requestUri` `requestUri` varchar(500),
CHANGE `from` `from` varchar(500),
CHANGE `to` `to` longtext,
CHANGE `cc` `cc` longtext,
CHANGE `bcc` `bcc` longtext,
CHANGE `subject` `subject` varchar(500)");


// namespace migration

// save all classes
$classList = new Object_Class_List();
$classes=$classList->load();
foreach($classes as $c){
    $c->save();
}

// save all custom layouts
$customLayouts = new Object_Class_CustomLayout_List();
$customLayouts = $customLayouts->load();
foreach ($customLayouts as $layout) {
    $layout->save();
}

// save all object bricks
$list = new Object_Objectbrick_Definition_List();
$list = $list->load();
foreach($list as $brickDefinition) {
    $brickDefinition->save();
}

// save all fieldcollections
$list = new Object_Fieldcollection_Definition_List();
$list = $list->load();
foreach ($list as $fc) {
    $fc->save();
}
