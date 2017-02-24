<!DOCTYPE html>
<html>
<head>
    <?php
    /** @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view */
    $redirect = $view->router()->path('pimcore_admin_login', [
        'deeplink' => 'true'
    ]);
    ?>

    <script type="text/javascript" src="/pimcore/static6/js/pimcore/namespace.js"></script>
    <script type="text/javascript" src="/pimcore/static6/js/pimcore/functions.js"></script>
    <script type="text/javascript" src="/pimcore/static6/js/pimcore/helpers.js"></script>
    <script type="text/javascript">
        <?php if ($tab) { ?>
            pimcore.helpers.clearOpenTab();
            pimcore.helpers.rememberOpenTab("<?= $tab ?>", true);
        <?php } ?>
        window.location.href = "<?= $redirect ?>";
    </script>
</head>
<body>
</body>
</html>
