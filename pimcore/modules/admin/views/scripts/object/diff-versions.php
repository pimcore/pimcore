<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/object_versions.css"/>

</head>

<body>


<?php

use Pimcore\Model\Object;

$fields = $this->object1->getClass()->getFieldDefinitions();
?>

<table class="preview" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <th>Name</th>
        <th>Key</th>
        <th>Version 1</th>
        <th>Version 2</th>
    </tr>
    <tr class="system">
        <td>Date</td>
        <td>o_modificationDate</td>
        <td><?php echo date('Y-m-d H:i:s', $this->object1->getModificationDate()); ?></td>
        <td><?php echo date('Y-m-d H:i:s', $this->object2->getModificationDate()); ?></td>
    </tr>
    <tr class="system">
        <td>Path</td>
        <td>o_path</td>
        <td><?php echo $this->object1->getFullpath(); ?></td>
        <td><?php echo $this->object2->getFullpath(); ?></td>
    </tr>
    <tr class="system">
        <td>Published</td>
        <td>o_published</td>
        <td><?php echo \Zend_Json::encode($this->object1->getPublished()); ?></td>
        <td><?php echo \Zend_Json::encode($this->object2->getPublished()); ?></td>
    </tr>

    <tr class="">
        <td colspan="3">&nbsp;</td>
    </tr>

<?php $c = 0; ?>
<?php
    foreach ($fields as $fieldName => $definition) { ?>
    <?php
        if($definition instanceof Object\ClassDefinition\Data\Localizedfields) { ?>
        <?php foreach(\Pimcore\Tool::getValidLanguages() as $language) { ?>
            <?php foreach ($definition->getFieldDefinitions() as $lfd) { ?>
                <?php
                    $v1 = $lfd->getVersionPreview($this->object1->getValueForFieldName($fieldName)->getLocalizedValue($lfd->getName(), $language));
                    $v2 = $lfd->getVersionPreview($this->object2->getValueForFieldName($fieldName)->getLocalizedValue($lfd->getName(), $language));
                ?>
                <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                    <td><?php echo $lfd->getTitle() ?> (<?php echo $language; ?>)</td>
                    <td><?php echo $lfd->getName() ?></td>
                    <td><?php echo $v1 ?></td>
                    <td<?php if ($v1 != $v2) { ?> class="modified"<?php } ?>><?php echo $v2 ?></td>
                </tr>
                <?php
                $c++;
            } ?>
        <?php } ?>
    <?php } else
            if($definition instanceof Object\ClassDefinition\Data\ObjectBricks) {
                ?>
                <?php foreach($definition->getAllowedTypes() as $asAllowedType) { ?>
                    <?php
                    $collectionDef = Object\Objectbrick\Definition::getByKey($asAllowedType);

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
                            <td><?php echo ucfirst($asAllowedType) . " - " . $lfd->getTitle() ?></td>
                            <td><?php echo $lfd->getName() ?></td>
                            <td><?php echo $v1 ?></td>
                            <td<?php if ($v1 != $v2) { ?> class="modified"<?php } ?>><?php echo $v2 ?></td>
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
            <td><?php echo $definition->getTitle() ?></td>
            <td><?php echo $definition->getName() ?></td>
            <td><?php echo $v1 ?></td>
            <td<?php if ($v1 != $v2) { ?> class="modified"<?php } ?>><?php echo $v2 ?></td>
        </tr>
    <?php } ?>
    <?php $c++;
} ?>
</table>


</body>
</html>