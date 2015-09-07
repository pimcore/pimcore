<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <script type="text/javascript" src="/pimcore/static/js/lib/prototype-light.js"></script>
</head>

<body>
<script type="text/javascript">

    parent.setTimeout(function () {
        this.pimcore.helpers.openAsset(<?= $this->asset->getId() ?>, "image");
    }.bind(parent), 1000);

    parent.pimcore.helpers.closeAsset(<?= $this->asset->getId() ?>);

</script>
</body>
</html>