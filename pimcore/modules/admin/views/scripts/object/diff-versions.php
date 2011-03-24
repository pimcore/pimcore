<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
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

<?php $c = 0; ?>
<?php foreach ($fields as $fieldName => $definition) { ?>
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
<?php $c++;
} ?>
</table>


</body>
</html>