<?php

function check($fieldDefinitions, $needsSave) {
    /** @var  $fieldDefinition */
    foreach ($fieldDefinitions as $fieldDefinition) {
        if ($fieldDefinition instanceof  \Pimcore\Model\Object\ClassDefinition\Data\Localizedfields) {
            $needsSave = check($fieldDefinition->getFieldDefinitions(), $needsSave);

        } else  if ($fieldDefinition instanceof \Pimcore\Model\Object\ClassDefinition\Data\Relations\AbstractRelations) {
            if (method_exists($fieldDefinition, "getLazyLoading") && $fieldDefinition->getLazyLoading()) {
                echo($fieldDefinition->getName() . "!!!\n");
                $needsSave |= true;
                $fieldDefinition->setLazyLoading(false);
            }
        }

    }
    return $needsSave;

}

$list = new \Pimcore\Model\Object\Fieldcollection\Definition\Listing();
$list = $list->load();

/** @var  $collectionDef \Pimcore\Model\Object\Fieldcollection\Definition*/
foreach ($list as $collectionDef) {
    $needsSave = false;

    $fieldDefinitions = $collectionDef->getFieldDefinitions();
    $needsSave |= check($fieldDefinitions, $needsSave);


    if ($needsSave) {
        $collectionDef->save();
    }

}
