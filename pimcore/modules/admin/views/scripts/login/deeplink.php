<!DOCTYPE html>
<html>
<head>
    <script type="text/javascript" src="/pimcore/static/js/pimcore/namespace.js"></script>
    <script type="text/javascript" src="/pimcore/static/js/pimcore/functions.js"></script>
    <script type="text/javascript" src="/pimcore/static/js/pimcore/helpers.js"></script>
    <script type="text/javascript">
        <?php if ($this->tab) { ?>
            pimcore.helpers.clearOpenTab();
            pimcore.helpers.rememberOpenTab("<?php echo $this->tab ?>", true);
        <?php } ?>
        window.location.href = "/admin/login/?deeplink=true";
    </script>
</head>
<body>


</body>
</html>