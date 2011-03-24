<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <script type="text/javascript" src="/pimcore/static/js/lib/prototype-light.js"></script>
</head>

<body>
<script type="text/javascript">

    parent.setTimeout(function () {
        this.pimcore.helpers.openAsset(<?php echo $this->asset->getId() ?>, "image");
    }.bind(parent), 1000);

    parent.pimcore.helpers.closeAsset(<?php echo $this->asset->getId() ?>);

</script>
</body>
</html>