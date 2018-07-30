<?php
/** @var $view \Pimcore\Templating\PhpEngine */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Pimcore!</title>

    <meta charset="UTF-8">
    <meta name="robots" content="noindex, follow"/>

    <link rel="icon" type="image/png" href="/bundles/pimcoreadmin/img/favicon/favicon-32x32.png"/>

    <link rel="stylesheet" href="/bundles/pimcoreadmin/css/login.css" type="text/css"/>
    <script src="/bundles/pimcoreadmin/js/lib/jquery-3.3.1.min.js"></script>

    <?php foreach ($this->pluginCssPaths as $pluginCssPath): ?>
        <link rel="stylesheet" type="text/css" href="<?= $pluginCssPath ?>?_dc=<?= $pluginDcValue; ?>"/>
    <?php endforeach; ?>
</head>
<body>


<?php $config = $this->config; ?>
<?php if ($config->general->loginscreencustomimage) { ?>
    <style type="text/css">
        body {
            background: url(<?= $config->general->loginscreencustomimage; ?>) no-repeat center center fixed;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
        }

        #header {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        #content {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
<?php } ?>

<?php if($config->branding) { ?>
    <?php if($config->branding->color_login_screen) {
        $customColor = $config->branding->color_login_screen;
        ?>
        <style type="text/css">
            #loginform button, #twofactorform button {
                background: <?= $customColor ?>;
            }

            a, a:hover, a:visited, a:active {
                color: <?= $customColor ?>;
            }
        </style>
    <?php } ?>
<?php } ?>



<?php $view->slots()->output('_content') ?>

<div id="footer">
    &copy; 2009-<?= date("Y") ?> <a href="http://www.pimcore.org/">pimcore GmbH</a>, a proud member of the
    <a href="http://www.elements.at/">elements group</a>
</div>

<?php if (!$config->general->loginscreencustomimage) { ?>
    <div id="background"></div>
    <div id="backgroundImageInfo"></div>
<?php } ?>

<script type="text/javascript" src="https://liveupdate.pimcore.org/imageservice"></script>

<?php $view->slots()->output('below_footer') ?>

</body>
</html>
