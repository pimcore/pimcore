<?php

$filesystem = new \Symfony\Component\Filesystem\Filesystem();
if ($filesystem->exists(PIMCORE_CLASS_DIRECTORY . '/Object')) {
    $filesystem->rename(PIMCORE_CLASS_DIRECTORY . '/Object', PIMCORE_CLASS_DIRECTORY . '/__please_delete_Object');

    $classList = new \Pimcore\Model\DataObject\ClassDefinition\Listing();
    $classes = $classList->load();
    foreach ($classes as $class) {
        $class->save();
    }

    $brickList = new \Pimcore\Model\DataObject\Objectbrick\Definition\Listing();
    $brickList = $brickList->load();
    foreach ($brickList as $brickDef) {
        $brickDef->save();
    }

    $fcList = new \Pimcore\Model\DataObject\Fieldcollection\Definition\Listing();
    $fcList = $fcList->load();
    foreach ($fcList as $collectionDef) {
        $collectionDef->save();
    }
}
