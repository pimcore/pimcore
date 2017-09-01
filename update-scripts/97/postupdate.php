<?php

function check($fieldDefinitions, $needsSave)
{
    /** @var $fieldDefinition */
    foreach ($fieldDefinitions as $fieldDefinition) {
        if ($fieldDefinition instanceof  \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields) {
            $needsSave = check($fieldDefinition->getFieldDefinitions(), $needsSave);
        } elseif ($fieldDefinition instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations) {
            if (method_exists($fieldDefinition, 'getLazyLoading') && $fieldDefinition->getLazyLoading()) {
                $needsSave |= true;
                $fieldDefinition->setLazyLoading(false);
            }
        }
    }

    return $needsSave;
}

$list = new \Pimcore\Model\DataObject\Fieldcollection\Definition\Listing();
$list = $list->load();

/** @var $collectionDef \Pimcore\Model\DataObject\Fieldcollection\Definition */
foreach ($list as $collectionDef) {
    $needsSave = false;

    $fieldDefinitions = $collectionDef->getFieldDefinitions();
    $needsSave |= check($fieldDefinitions, $needsSave);

    if ($needsSave) {
        $collectionDef->save();
    }
}
