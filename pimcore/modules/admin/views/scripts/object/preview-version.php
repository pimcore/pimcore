<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/object_versions.css"/>

</head>

<body>


<?php $fields = $this->object->geto_class()->getFieldDefinitions(); ?>

<table class="preview" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <th>Name</th>
        <th>Key</th>
        <th>Value</th>
    </tr>

<?php $c = 0; ?>
<?php foreach ($fields as $fieldName => $definition) { ?>
    <tr<?php if ($c % 2) { ?> class="odd"<?php } ?>>
        <td><?php echo $definition->getTitle() ?></td>
        <td><?php echo $definition->getName() ?></td>
        <td><?php echo $definition->getVersionPreview($this->object->getValueForFieldName($fieldName)) ?></td>
    </tr>
<?php $c++;
} ?>
</table>


</body>
</html>