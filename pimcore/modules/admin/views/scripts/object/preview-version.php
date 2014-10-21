<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/object_versions.css"/>

</head>

<body>


<?php

    use Pimcore\Model\Object;

    $fields = $this->object->getClass()->getFieldDefinitions();

?>

<table class="preview" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <th>Name</th>
        <th>Key</th>
        <th>Value</th>
    </tr>
    <tr class="system">
        <td>Date</td>
        <td>o_modificationDate</td>
        <td><?php echo date('Y-m-d H:i:s', $this->object->getModificationDate()); ?></td>
    </tr>
    <tr class="system">
        <td>Path</td>
        <td>o_path</td>
        <td><?php echo $this->object->getFullpath(); ?></td>
    </tr>
    <tr class="system">
        <td>Published</td>
        <td>o_published</td>
        <td><?php echo \Zend_Json::encode($this->object->getPublished()); ?></td>
    </tr>

    <tr class="">
        <td colspan="3">&nbsp;</td>
    </tr>

<?php $c = 0; ?>
<?php foreach ($fields as $fieldName => $definition) { ?>
    <?php if($definition instanceof Object\ClassDefinition\Data\Localizedfields) { ?>
        <?php foreach(\Pimcore\Tool::getValidLanguages() as $language) { ?>
            <?php foreach ($definition->getFieldDefinitions() as $lfd) { ?>
                <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                    <td><?php echo $lfd->getTitle() ?> (<?php echo $language; ?>)</td>
                    <td><?php echo $lfd->getName() ?></td>
                    <td>
                        <?php
                            if($this->object->getValueForFieldName($fieldName)) {
                                echo $lfd->getVersionPreview($this->object->getValueForFieldName($fieldName)->getLocalizedValue($lfd->getName(), $language));
                            }
                        ?>
                    </td>
                </tr>
            <?php
                $c++;
            } ?>
    <?php } ?>
    <?php } else if($definition instanceof Object\ClassDefinition\Data\Objectbricks){ ?>
            <?php foreach($definition->getAllowedTypes() as $asAllowedType) { ?>
                <?php
                $collectionDef = Object\Objectbrick\Definition::getByKey($asAllowedType);

                foreach ($collectionDef->getFieldDefinitions() as $lfd) { ?>
                    <?php
                    $value = null;
                    $bricks = $this->object->{"get" . ucfirst($fieldName)}();
                    if ($bricks) {
                        $brickValue = $bricks->{"get" . $asAllowedType}();
                        if ($brickValue) {
                            $value = $lfd->getVersionPreview($brickValue->getValueForFieldName($lfd->getName()));
                        }
                    }
                    if (!$bricks) {
                        continue;
                    }
                    ?>
                     <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
                        <td><?php echo ucfirst($asAllowedType) . " - " . $lfd->getTitle() ?> (<?php echo $language; ?>)</td>
                        <td><?php echo $lfd->getName() ?></td>
                        <td><?php echo $value ?></td>
                    </tr>
                    <?php
                    $c++;
                } ?>
            <?php } ?>
    <?php } else { ?>
        <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
            <td><?php echo $definition->getTitle() ?></td>
            <td><?php echo $definition->getName() ?></td>
            <td><?php echo $definition->getVersionPreview($this->object->getValueForFieldName($fieldName)) ?></td>
        </tr>
    <?php } ?>
<?php $c++;
} ?>
</table>


</body>
</html>