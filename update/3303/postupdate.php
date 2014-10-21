<?php

// get db connection
$db = \Pimcore\Resource::get();
$db->query("ALTER TABLE `email_log`
CHANGE `requestUri` `requestUri` varchar(500),
CHANGE `from` `from` varchar(500),
CHANGE `to` `to` longtext,
CHANGE `cc` `cc` longtext,
CHANGE `bcc` `bcc` longtext,
CHANGE `subject` `subject` varchar(500)");


// namespace migration

// save all classes
$classList = new Pimcore\Model\Object\ClassDefinition\Listing();
$classes=$classList->load();
foreach($classes as $c){
    $c->save();
}

// save all custom layouts
$customLayouts = new Pimcore\Model\Object\ClassDefinition\CustomLayout\Listing();
$customLayouts = $customLayouts->load();
foreach ($customLayouts as $layout) {
    $layout->save();
}

// save all object bricks
$list = new Pimcore\Model\Object\Objectbrick\Definition\Listing();
$list = $list->load();
foreach($list as $brickDefinition) {
    $brickDefinition->save();
}

// save all fieldcollections
$list = new Pimcore\Model\Object\Fieldcollection\Definition\Listing();
$list = $list->load();
foreach ($list as $fc) {
    $fc->save();
}
