<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="/bundles/pimcoreadmin/css/object_versions.css"/>
</head>

<body>

<?php

use Pimcore\Model\DataObject;

$this->get('translate')->setDomain('admin');
$fields = $this->object1->getClass()->getFieldDefinitions();
?>

<table class="preview" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <th>Name</th>
        <th>Key</th>
        <?php if ($this->isImportPreview) { ?>
            <?php if ($this->isNew) { ?>
                <th>New Object or unable to resolve</th>
            <?php } else { ?>
                <th>Before</th>
                <th>After</th>
            <?php } ?>
        <?php } else { ?>
            <th>Version 1</th>
            <th>Version 2</th>
        <?php } ?>
    </tr>
    <tr class="system">
        <td>Date</td>
        <td>o_modificationDate</td>
        <?php if (!$this->isImportPreview || !$this->isNew) { ?>
            <td><?= date('Y-m-d H:i:s', $this->object1->getModificationDate()); ?></td>
        <?php }?>
        <td><?= date('Y-m-d H:i:s', $this->object2->getModificationDate()); ?></td>
    </tr>
    <tr class="system">
        <td>Path</td>
        <td>o_path</td>
        <?php if (!$this->isImportPreview || !$this->isNew) { ?>
            <td><?= $this->object1->getRealFullPath(); ?></td>
        <?php } ?>
        <td<?php if ($this->object1->getRealFullPath() !== $this->object2->getRealFullPath()) { ?> class="modified"<?php } ?>><?= $this->object2->getRealFullPath(); ?></td>
    </tr>
    <tr class="system">
        <td>Published</td>
        <td>o_published</td>
        <?php if (!$this->isImportPreview || !$this->isNew) { ?>
            <td><?= json_encode($this->object1->getPublished()); ?></td>
        <?php } ?>
        <td<?php if ($this->object1->getPublished() !== $this->object2->getPublished()) { ?> class="modified"<?php } ?>><?= json_encode($this->object2->getPublished()); ?></td>
    </tr>
    <tr class="system">
        <td>Id</td>
        <td>o_id</td>
        <?php if (!$this->isImportPreview || !$this->isNew) { ?>
            <td><?= json_encode($this->object1->getId()); ?></td>
        <?php } ?>
        <td><?= json_encode($this->object2->getId()); ?></td>
    </tr>


    <tr class="">
        <td colspan="3">&nbsp;</td>
    </tr>

    <?php $c = 0; ?>
    <?php
    foreach ($fields as $fieldName => $definition) { ?>
        <?php
        if($definition instanceof DataObject\ClassDefinition\Data\Localizedfields) { ?>
            <?php foreach(\Pimcore\Tool::getValidLanguages() as $language) { ?>
                <?php foreach ($definition->getFieldDefinitions() as $lfd) { ?>
                    <?php
                    $v1Container = $this->object1->getValueForFieldName($fieldName);
                    $v1 = $v1Container ? $lfd->getVersionPreview($v1Container->getLocalizedValue($lfd->getName(), $language)) : "";
                    $v2Container = $this->object2->getValueForFieldName($fieldName);
                    $v2 = $v2Container ? $lfd->getVersionPreview($v2Container->getLocalizedValue($lfd->getName(), $language)) : "";
                    ?>
                    <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                        <td><?= $this->translate($lfd->getTitle()) ?> (<?= $language; ?>)</td>
                        <td><?= $lfd->getName() ?></td>
                        <?php if (!$this->isImportPreview || !$this->isNew) { ?>
                            <td><?= $v1 ?></td>
                        <?php } ?>
                        <td<?php if ($v1 != $v2) { ?> class="modified"<?php } ?>><?= $v2 ?></td>
                    </tr>
                    <?php
                    $c++;
                } ?>
            <?php } ?>
        <?php } else if($definition instanceof DataObject\ClassDefinition\Data\Classificationstore){

            /** @var DataObject\Classificationstore $storedata1 */
            $storedata1 = $definition->getVersionPreview($this->object1->getValueForFieldName($fieldName));
            /** @var DataObject\Classificationstore $storedata2 */
            $storedata2 = $definition->getVersionPreview($this->object2->getValueForFieldName($fieldName));

            $existingGroups = array();


            if ($storedata1) {
                $activeGroups1 = $storedata1->getActiveGroups();
            } else {
                $activeGroups1 = array();
            }

            if ($storedata2) {
                $activeGroups2 = $storedata2->getActiveGroups();
            } else {
                $activeGroups2 = array();
            }

            foreach ($activeGroups1 as $activeGroupId => $enabled) {
                $existingGroups[$activeGroupId] = $activeGroupId;
            }

            foreach ($activeGroups2 as $activeGroupId => $enabled) {
                $existingGroups[$activeGroupId] = $enabled;
            }

            if (!$existingGroups) {
                continue;
            }

            $languages = array("default");

            if ($definition->isLocalized()) {
                $languages = array_merge($languages, \Pimcore\Tool::getValidLanguages());
            }

            foreach ($existingGroups as $activeGroupId => $enabled) {
                if  (!$activeGroups1[$activeGroupId] && !$activeGroups2[$activeGroupId]) {
                    continue;
                }
                /** @var DataObject\Classificationstore\GroupConfig $groupDefinition */
                $groupDefinition = Pimcore\Model\DataObject\Classificationstore\GroupConfig::getById($activeGroupId);
                if (!$groupDefinition) {
                    continue;
                }

                $keyGroupRelations = $groupDefinition->getRelations();

                /** @var DataObject\Classificationstore\KeyGroupRelation $keyGroupRelation */
                foreach ($keyGroupRelations as $keyGroupRelation) {

                    $keyDef = DataObject\Classificationstore\Service::getFieldDefinitionFromJson(json_decode($keyGroupRelation->getDefinition()), $keyGroupRelation->getType());
                    if (!$keyDef) {
                        continue;
                    }

                    foreach ($languages as $language) {
                        $keyData1 = $storedata1 ? $storedata1->getLocalizedKeyValue($activeGroupId, $keyGroupRelation->getKeyId(), $language, true, true) : null;
                        $preview1 = $keyDef->getVersionPreview($keyData1);

                        $keyData2 = $storedata2 ? $storedata2->getLocalizedKeyValue($activeGroupId, $keyGroupRelation->getKeyId(), $language, true, true) : null;
                        $preview2 = $keyDef->getVersionPreview($keyData2);
                        ?>

                        <tr class = "<?php if ($c % 2) { ?> odd<?php  } ?>">
                            <td><?= $this->translate($definition->getTitle()) ?></td>
                            <td><?= $groupDefinition->getName() ?> - <?= $keyGroupRelation->getName()?> <?= $definition->isLocalized() ? "/ " . $language : "" ?></td>
                            <?php if (!$this->isImportPreview || !$this->isNew) { ?>
                                <td><?= $preview1 ?></td>
                            <?php } ?>
                            <td <?php if (!$keyDef->isEqual($keyData1, $keyData2)) { ?> class="modified"<?php } ?>><?= $preview2 ?></td>
                        </tr>
                        <?php
                        $c++;
                    }
                }
            }
            ?>
        <?php } else if ($definition instanceof DataObject\ClassDefinition\Data\Objectbricks) {
            ?>
            <?php foreach ($definition->getAllowedTypes() as $asAllowedType) { ?>
                <?php
                $collectionDef = DataObject\Objectbrick\Definition::getByKey($asAllowedType);

                foreach ($collectionDef->getFieldDefinitions() as $lfd) { ?>
                    <?php


                    $bricks1 = $this->object1->{"get" . ucfirst($fieldName)}();
                    $bricks2 = $this->object2->{"get" . ucfirst($fieldName)}();

                    if (!$bricks1 && !$bricks2) {
                        continue;
                    }


                    if ($lfd instanceof DataObject\ClassDefinition\Data\Localizedfields) { ?>
                        <?php foreach (\Pimcore\Tool::getValidLanguages() as $language) { ?>
                            <?php foreach ($lfd->getFieldDefinitions() as $localizedFieldDefinition) { ?>
                                <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                                    <td><?= $this->translate($localizedFieldDefinition->getTitle()) ?> (<?= $language; ?>)</td>
                                    <td><?= $localizedFieldDefinition->getName() ?></td>

                                    <?php
                                    $v1 = null;
                                    $v2 = null;
                                    if ($bricks1) {
                                        $brick1Value = $bricks1->{"get" . $asAllowedType}();
                                        if ($brick1Value) {
                                            /** @var DataObject\Localizedfield $localizedBrickValues */
                                            $localizedBrickValues = $brick1Value->getLocalizedFields();
                                            $localizedBrickValue = $localizedBrickValues->getLocalizedValue($localizedFieldDefinition->getName(), $language);
                                            $v1 = $localizedFieldDefinition->getVersionPreview($localizedBrickValue);
                                        }
                                    }

                                    if ($bricks2) {
                                        $brick2Value = $bricks2->{"get" . $asAllowedType}();
                                        if ($brick2Value) {
                                            /** @var DataObject\Localizedfield $localizedBrickValues */
                                            $localizedBrickValues = $brick2Value->getLocalizedFields();
                                            $localizedBrickValue = $localizedBrickValues->getLocalizedValue($localizedFieldDefinition->getName(), $language);
                                            $v2 = $localizedFieldDefinition->getVersionPreview($localizedBrickValue);
                                        }
                                    }

                                    ?>
                                    <?php if (!$this->isImportPreview || !$this->isNew) { ?>
                                        <td><?= $v1 ?></td>
                                    <?php } ?>
                                    <td<?php if ($v1 !== $v2) { ?> class="modified"<?php } ?>><?= $v2 ?></td>

                                </tr>
                                <?php
                                $c++;
                            } ?>
                        <?php }
                    } else {
                        $v1 = null;
                        if ($bricks1) {
                            $brick1Value = $bricks1->{"get" . $asAllowedType}();
                            if ($brick1Value) {
                                $v1 = $lfd->getVersionPreview($brick1Value->getValueForFieldName($lfd->getName()));
                            }
                        }
                        $v2 = null;

                        if ($bricks2) {
                            $brick2Value = $bricks2->{"get" . $asAllowedType}();
                            if ($brick2Value) {
                                $v2 = $lfd->getVersionPreview($brick2Value->getValueForFieldName($lfd->getName()));
                            }
                        }


                        ?>
                        <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                            <td><?= ucfirst($asAllowedType) . " - " . $this->translate($lfd->getTitle()) ?></td>
                            <td><?= $lfd->getName() ?></td>
                            <?php if (!$this->isImportPreview || !$this->isNew) { ?>
                                <td><?= $v1 ?></td>
                            <?php } ?>
                            <td<?php if ($v1 !== $v2) { ?> class="modified"<?php } ?>><?= $v2 ?></td>
                        </tr>

                        <?php
                        $c++;
                    }

                } ?>
            <?php } ?>
        <?php } else if ($definition instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
            $fields1 = $this->object1->{"get" . ucfirst($fieldName)}();
            $fields2 = $this->object2->{"get" . ucfirst($fieldName)}();
            $fieldDefinitions1 = null;
            $fieldItems1 = null;
            $fieldDefinitions2 = null;
            $fieldItems2 = null;

            if ($fields1) {
                $fieldDefinitions1 = $fields1->getItemDefinitions();
                $fieldItems1 = $fields1->getItems();
            }

            if ($fields2) {
                $fieldDefinitions2 = $fields2->getItemDefinitions();
                $fieldItems2 = $fields2->getItems();
            }

            if (!is_null($fieldItems1) && count($fieldItems1)) {
                foreach ($fieldItems1 as $fkey1 => $fieldItem1) {
                    $fieldKeys1 = $fieldDefinitions1[$fieldItem1->getType()]->getFieldDefinitions();

                    if (isset($fieldItems2[$fkey1]) && $fieldItem1->getType() == $fieldItems2[$fkey1]->getType()) {
                        $ffkey2 = $fieldItems2[$fkey1];
                        $fieldKeys2 = $fieldDefinitions2[$ffkey2->getType()]->getFieldDefinitions();
                        unset($fieldItems2[$fkey1]);
                    }
                    foreach ($fieldKeys1 as $fkey => $fieldKey1) {
                        $v2 = null;
                        $v1 = $fieldKey1->getVersionPreview($fieldItem1->{"get" . ucfirst($fieldKey1->name)}());

                        if(!empty($ffkey2) && isset($fieldKeys2[$fkey])) {
                            $v2 = $fieldKey1->getVersionPreview($ffkey2->{"get" . ucfirst($fieldKeys2[$fkey]->name)}());
                        }

                        ?>
                        <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                            <td><?= ucfirst($fieldItem1->getType()) . " - " . $this->translate($fieldKey1->title) ?></td>
                            <td><?= $fieldKey1->name ?></td>
                            <?php if (!$this->isImportPreview || !$this->isNew) { ?>
                                <td><?= $v1 ?></td>
                            <?php } ?>
                            <td<?php if ($v1 !== $v2 || !isset($v2)) { ?> class="modified"<?php } ?>><?= $v2 ?></td>
                        </tr>
                        <?php
                        $c++;
                    }
                }
            }

            if (!is_null($fieldItems2) && count($fieldItems2)) {
                foreach ($fieldItems2 as $fkey2 => $fieldItem2) {
                    $fieldKeys2 = $fieldDefinitions2[$fieldItem2->getType()]->getFieldDefinitions();
                    foreach ($fieldKeys2 as $fkey => $fieldKey2) {
                        $v1 = null;
                        $v2 = null;
                        $v2 = $fieldKey2->getVersionPreview($fieldItem2->{"get" . ucfirst($fieldKey2->name)}());

                        ?>
                        <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                            <td><?= ucfirst($fieldItem2->getType()) . " - " . $this->translate($fieldKey2->title) ?></td>
                            <td><?= $fieldKey2->name ?></td>
                            <?php if (!$this->isImportPreview || !$this->isNew) { ?>
                                <td><?= $v1 ?></td>
                            <?php } ?>
                            <td<?php if ($v1 !== $v2) { ?> class="modified"<?php } ?>><?= $v2 ?></td>
                        </tr>
                        <?php
                        $c++;
                    }
                }
            }
        } else { ?>
            <?php
            $v1 = $definition->getVersionPreview($this->object1->getValueForFieldName($fieldName));
            $v2 = $definition->getVersionPreview($this->object2->getValueForFieldName($fieldName));
            ?>
            <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                <td><?= $this->translate($definition->getTitle()) ?></td>
                <td><?= $definition->getName() ?></td>
                <?php if (!$this->isImportPreview || !$this->isNew) { ?>
                    <td><?= $v1 ?></td>
                <?php } ?>
                <td<?php if ($v1 !== $v2) { ?> class="modified"<?php } ?>><?= $v2 ?></td>
            </tr>

        <?php } ?>
        <?php $c++;
    } ?>
</table>


</body>
</html>
