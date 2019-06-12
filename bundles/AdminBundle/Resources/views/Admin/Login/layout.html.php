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
    <script src="/bundles/pimcoreadmin/js/lib/jquery-3.4.1.min.js"></script>

    <?php foreach ($this->pluginCssPaths as $pluginCssPath): ?>
        <link rel="stylesheet" type="text/css" href="<?= $pluginCssPath ?>?_dc=<?= $pluginDcValue; ?>"/>
    <?php endforeach; ?>
</head>
<body class="pimcore_version_6">

<?php
    $config = $this->config;
    if ($config->general->loginscreencustomimage) {
        $backgroundImageUrl = $config->general->loginscreencustomimage;
    } else {
        $defaultImages = ['pimconaut-ecommerce.svg', 'pimconaut-world.svg', 'pimconaut-engineer.svg', 'pimconaut-moon.svg', 'pimconaut-rocket.svg'];
        $backgroundImageUrl = '/bundles/pimcoreadmin/img/login/' . $defaultImages[array_rand($defaultImages)];
    }
?>

    <style type="text/css">
        #background {
            background-image: url(<?= $backgroundImageUrl ?>);
        }
    </style>

<?php if($config->branding) { ?>
    <?php if($config->branding->color_login_screen) {
        $customColor = $config->branding->color_login_screen;
        ?>
        <style type="text/css">
            #content button {
                background: <?= $customColor ?>;
            }
        </style>
    <?php } ?>
<?php } ?>

<div id="logo">
    <img src="/admin/settings/display-custom-logo?white=true">
</div>

<div id="content">
    <?php $view->slots()->output('_content') ?>
</div>

<?php /*
<div id="news">
    <h2>News</h2>
    <hr>
    <p>
        <a href="#">Where is Master Data Management Heading in the Future?</a>
    </p>
    <hr>
    <p>
        <a href="#">Priint and Pimcore announce technology partnership to ease publishing workflows</a>
    </p>
</div>
 */ ?>

<div id="contentBackground"></div>
<div id="background"></div>
<div id="footer">
    &copy; 2009-<?= date("Y") ?> <a href="http://www.pimcore.org/">Pimcore GmbH</a><br>
    BE RESPECTFUL AND HONOR OUR WORK FOR FREE & OPEN SOURCE SOFTWARE BY NOT REMOVING OUR COPYRIGHT NOTICE!
    KEEP IN MIND THAT REMOVING THE COPYRIGHT NOTICE IS VIOLATING OUR LICENSING TERMS!
</div>


<script src="/bundles/pimcoreadmin/js/lib/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="https://liveupdate.pimcore.org/imageservice"></script>

<?php $view->slots()->output('below_footer') ?>

</body>
</html>
