<?php
/** @var \Pimcore\Templating\PhpEngine $view */
/** @var \Pimcore\Config $config */

$config = $this->config;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Pimcore!</title>

    <meta charset="UTF-8">
    <meta name="robots" content="noindex, follow"/>

    <link rel="icon" type="image/png" href="/bundles/pimcoreadmin/img/favicon/favicon-32x32.png"/>

    <link rel="stylesheet" href="/bundles/pimcoreadmin/css/login.css" type="text/css"/>

    <?php foreach ($this->pluginCssPaths as $pluginCssPath): ?>
        <link rel="stylesheet" type="text/css" href="<?= $pluginCssPath ?>?_dc=<?= time(); ?>"/>
    <?php endforeach; ?>
</head>
<body class="pimcore_version_6 <?= $config['branding']['login_screen_invert_colors'] ? 'inverted' : '' ?>">

<?php
    $customImage = $config['branding']['login_screen_custom_image'];
    //https://github.com/pimcore/pimcore/issues/8016
    //https://github.com/pimcore/pimcore/issues/8129
    if (preg_match('@^https?://@', $customImage)) {
        $backgroundImageUrl = $customImage;
    } elseif (is_file(PIMCORE_WEB_ROOT . $customImage)) {
        $backgroundImageUrl = $customImage;
    } else {
        $defaultImages = ['pimconaut-ecommerce.svg', 'pimconaut-world.svg', 'pimconaut-engineer.svg', 'pimconaut-moon.svg', 'pimconaut-rocket.svg'];
        $backgroundImageUrl = '/bundles/pimcoreadmin/img/login/' . $defaultImages[array_rand($defaultImages)];
    }
?>

    <style type="text/css">
        #background {
            background-image: url("<?= $backgroundImageUrl ?>");
        }
    </style>

<?php if (!empty($customColor = $config['branding']['color_login_screen'])) { ?>
    <style type="text/css">
        #content button {
            background: <?= $customColor ?>;
        }

        #content a {
            color: <?= $customColor ?>;
        }
    </style>
<?php } ?>

<div id="logo">
    <img src="<?=$view->router()->path('pimcore_settings_display_custom_logo')?><?= $config['branding']['login_screen_invert_colors'] ? '' : '?white=true' ?>">
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

<script type="text/javascript" src="https://liveupdate.pimcore.org/imageservice"></script>

<?php $view->slots()->output('below_footer') ?>

</body>
</html>
