<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/object_versions.css"/>

</head>

<body>


<?php 
$fields = $this->object1->geto_class()->getFieldDefinitions();
?>

<table class="preview" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <th>Name</th>
        <th>Key</th>
        <th>Version 1</th>
        <th>Version 2</th>
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
        <td><?php echo Zend_Json::encode($this->object1->getPublished()); ?></td>
        <td><?php echo Zend_Json::encode($this->object2->getPublished()); ?></td>
    </tr>

    <tr class="">
        <td colspan="3">&nbsp;</td>
    </tr>

<?php $c = 0; ?>
<?php foreach ($fields as $fieldName => $definition) { ?>
    <?php if($definition instanceof Object_Class_Data_Localizedfields) { ?>
        <?php foreach(Pimcore_Tool::getValidLanguages() as $language) { ?>
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
    <?php } else { ?>
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