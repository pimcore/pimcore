<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="/pimcore/static6/css/object_versions.css"/>
</head>

<body>

<?php

use Pimcore\Model\DataObject;

$fields = $this->object1->getClass()->getFieldDefinitions();
?>

<table class="preview" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <th>Name</th>
        <th>Key</th>
        <?php if ($this->isImportPreview) { ?>
            <th>Before</th>
            <th>After</th>
        <?php } else { ?>
            <th>Version 1</th>
            <th>Version 2</th>
        <?php } ?>
    </tr>
    <tr class="system">
        <td>Date</td>
        <td>o_modificationDate</td>
        <td><?= date('Y-m-d H:i:s', $this->object1->getModificationDate()); ?></td>
        <td><?= date('Y-m-d H:i:s', $this->object2->getModificationDate()); ?></td>
    </tr>
    <tr class="system">
        <td>Path</td>
        <td>o_path</td>
        <td><?= $this->object1->getRealFullPath(); ?></td>
        <td><?= $this->object2->getRealFullPath(); ?></td>
    </tr>
    <tr class="system">
        <td>Published</td>
        <td>o_published</td>
        <td><?= json_encode($this->object1->getPublished()); ?></td>
        <td><?= json_encode($this->object2->getPublished()); ?></td>
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
                    <td><?= $lfd->getTitle() ?> (<?= $language; ?>)</td>
                    <td><?= $lfd->getName() ?></td>
                    <td><?= $v1 ?></td>
                    <td<?php if ($v1 != $v2) { ?> class="modified"<?php } ?>><?= $v2 ?></td>
                </tr>
                <?php
                $c++;
            } ?>
        <?php } ?>
        <?php } else if($definition instanceof DataObject\ClassDefinition\Data\Classificationstore){



            /** @var $storedata DataObject\Classificationstore */
            $storedata1 = $definition->getVersionPreview($this->object1->getValueForFieldName($fieldName));
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
                /** @var $groupDefinition DataObject\Classificationstore\GroupConfig */
                $groupDefinition = Pimcore\Model\DataObject\Classificationstore\GroupConfig::getById($activeGroupId);
                if (!$groupDefinition) {
                    continue;
                }

                /** @var $keyGroupRelation DataObject\Classificationstore\KeyGroupRelation */
                $keyGroupRelations = $groupDefinition->getRelations();

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
                            <td><?= $definition->getTitle() ?></td>
                            <td><?= $groupDefinition->getName() ?> - <?= $keyGroupRelation->getName()?> <?= $definition->isLocalized() ? "/ " . $language : "" ?></td>
                            <td><?= $preview1 ?></td>
                            <td><?= $preview2 ?></td>
                        </tr>
                        <?php
                        $c++;
                    }
                }
            }
            ?>
    <?php } else if($definition instanceof DataObject\ClassDefinition\Data\ObjectBricks) {
                ?>
                <?php foreach($definition->getAllowedTypes() as $asAllowedType) { ?>
                    <?php
                    $collectionDef = DataObject\Objectbrick\Definition::getByKey($asAllowedType);

                    foreach ($collectionDef->getFieldDefinitions() as $lfd) { ?>
                        <?php

                        $v1 = null;
                        $bricks1 = $this->object1->{"get" . ucfirst($fieldName)}();
                        if ($bricks1) {
                            $brick1Value = $bricks1->{"get" . $asAllowedType}();
                            if ($brick1Value) {
                                $v1 = $lfd->getVersionPreview($brick1Value->getValueForFieldName($lfd->getName()));
                            }
                        }
                        $v2 = null;
                        $bricks2 = $this->object2->{"get" . ucfirst($fieldName)}();
                        if ($bricks2) {
                            $brick2Value = $bricks2->{"get" . $asAllowedType}();
                            if ($brick2Value) {
                                $v2 = $lfd->getVersionPreview($brick2Value->getValueForFieldName($lfd->getName()));
                            }
                        }
                        if (!$bricks1 && !$bricks2) {
                            continue;
                        }

                        ?>
                        <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                            <td><?= ucfirst($asAllowedType) . " - " . $lfd->getTitle() ?></td>
                            <td><?= $lfd->getName() ?></td>
                            <td><?= $v1 ?></td>
                            <td<?php if ($v1 != $v2) { ?> class="modified"<?php } ?>><?= $v2 ?></td>
                        </tr>
                        <?php
                        $c++;
                    } ?>
                <?php } ?>
            <?php } else
            { ?>
        <?php
            $v1 = $definition->getVersionPreview($this->object1->getValueForFieldName($fieldName));
            $v2 = $definition->getVersionPreview($this->object2->getValueForFieldName($fieldName));
        ?>
        <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
            <td><?= $definition->getTitle() ?></td>
            <td><?= $definition->getName() ?></td>
            <td><?= $v1 ?></td>
            <td<?php if ($v1 != $v2) { ?> class="modified"<?php } ?>><?= $v2 ?></td>
        </tr>

    <?php } ?>
    <?php $c++;
} ?>
</table>


</body>
</html>
