<?php
/** @var \Pimcore\Templating\PhpEngine $view */
/** @var \Pimcore\Templating\PhpEngine $this */
/** @var \Pimcore\Templating\GlobalVariables $app */
$app = $view->app;

$language = $app->getRequest()->getLocale();
$this->get("translate")->setDomain("admin");

/** @var \Pimcore\Bundle\AdminBundle\Security\User\User $userProxy */
$userProxy = $app->getUser();
$user      = $userProxy->getUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>

    <link rel="icon" type="image/png" href="/bundles/pimcoreadmin/img/favicon/favicon-32x32.png"/>
    <meta name="google" value="notranslate">

    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        #pimcore_loading {
            margin: 0 auto;
            width: 300px;
            padding: 300px 0 0 0;
            text-align: center;
        }

        .spinner {
            margin: 100px auto 0;
            width: 70px;
            text-align: center;
        }

        .spinner > div {
            width: 18px;
            height: 18px;
            background-color: #3d3d3d;

            border-radius: 100%;
            display: inline-block;
            -webkit-animation: sk-bouncedelay 1.4s infinite ease-in-out both;
            animation: sk-bouncedelay 1.4s infinite ease-in-out both;
        }

        .spinner .bounce1 {
            -webkit-animation-delay: -0.32s;
            animation-delay: -0.32s;
        }

        .spinner .bounce2 {
            -webkit-animation-delay: -0.16s;
            animation-delay: -0.16s;
        }

        @-webkit-keyframes sk-bouncedelay {
            0%, 80%, 100% {
                -webkit-transform: scale(0)
            }
            40% {
                -webkit-transform: scale(1.0)
            }
        }

        @keyframes sk-bouncedelay {
            0%, 80%, 100% {
                -webkit-transform: scale(0);
                transform: scale(0);
            }
            40% {
                -webkit-transform: scale(1.0);
                transform: scale(1.0);
            }
        }

        #pimcore_panel_tabs-body {
            background-image: url(<?=$this->router()->path('pimcore_settings_display_custom_logo')?>);
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 500px auto;
        }
    </style>

    <title><?= htmlentities(\Pimcore\Tool::getHostname(), ENT_QUOTES, 'UTF-8') ?> :: Pimcore</title>

    <script>
        var pimcore = {}; // namespace

        // hide symfony toolbar by default
        var symfonyToolbarKey = 'symfony/profiler/toolbar/displayState';
        if(!window.localStorage.getItem(symfonyToolbarKey)) {
            window.localStorage.setItem(symfonyToolbarKey, 'none');
        }
    </script>

    <script src="<?php echo $view->assets()->getUrl('bundles/fosjsrouting/js/router.js') ?>"></script>
    <script src="<?php echo $view->router()->path('fos_js_routing_js', array('callback' => 'fos.Router.setData')) ?>"></script>
</head>

<body class="pimcore_version_6">

<div id="pimcore_loading">
    <div class="spinner">
        <div class="bounce1"></div>
        <div class="bounce2"></div>
        <div class="bounce3"></div>
    </div>
</div>

<?php
$runtimePerspective = \Pimcore\Config::getRuntimePerspective($user);
?>

<div id="pimcore_sidebar">
    <div id="pimcore_navigation" style="display:none;">
        <ul>
            <?php if (\Pimcore\Config::inPerspective($runtimePerspective, "file")) { ?>
                <li id="pimcore_menu_file" data-menu-tooltip="<?= $this->translate("file") ?>" class="pimcore_menu_item pimcore_menu_needs_children">
                    <img src="/bundles/pimcoreadmin/img/material-icons/outline-file-24px.svg">
                </li>
            <?php } ?>
            <?php if (\Pimcore\Config::inPerspective($runtimePerspective, "extras")) { ?>
                <li id="pimcore_menu_extras" data-menu-tooltip="<?= $this->translate("tools") ?>" class="pimcore_menu_item pimcore_menu_needs_children">
                    <img src="/bundles/pimcoreadmin/img/material-icons/outline-build-24px.svg">
                </li>
            <?php } ?>
            <?php if (\Pimcore\Config::inPerspective($runtimePerspective, "marketing")) { ?>
                <li id="pimcore_menu_marketing" data-menu-tooltip="<?= $this->translate("marketing") ?>" class="pimcore_menu_item pimcore_menu_needs_children">
                    <img src="/bundles/pimcoreadmin/img/material-icons/outline-bar_chart-24px.svg">
                </li>
            <?php } ?>
            <?php if (\Pimcore\Config::inPerspective($runtimePerspective, "settings")) { ?>
                <li id="pimcore_menu_settings" data-menu-tooltip="<?= $this->translate("settings") ?>" class="pimcore_menu_item pimcore_menu_needs_children">
                    <img src="/bundles/pimcoreadmin/img/material-icons/outline-settings-24px.svg">
                </li>
            <?php } ?>
            <?php if (\Pimcore\Config::inPerspective($runtimePerspective, "ecommerce")) { ?>
                <li id="pimcore_menu_ecommerce" data-menu-tooltip="<?= $this->translate("bundle_ecommerce_mainmenu") ?>" class="pimcore_menu_item pimcore_menu_needs_children" style="display: none;">
                    <img src="/bundles/pimcoreadmin/img/material-icons/outline-shopping_cart-24px.svg">
                </li>
            <?php } ?>
            <?php if (\Pimcore\Config::inPerspective($runtimePerspective, "search")) { ?>
                <li id="pimcore_menu_search" data-menu-tooltip="<?= $this->translate("search") ?>" class="pimcore_menu_item pimcore_menu_needs_children">
                    <img src="/bundles/pimcoreadmin/img/material-icons/outline-search-24px.svg">
                </li>
            <?php } ?>
            <li id="pimcore_menu_maintenance" data-menu-tooltip="<?= $this->translate("deactivate_maintenance") ?>" class="pimcore_menu_item " style="display:none;"></li>
        </ul>
    </div>

    <div id="pimcore_status"></div>

    <div id="pimcore_notification" data-menu-tooltip="<?= $this->translate("notifications") ?>" class="pimcore_icon_comments">
        <img src="/bundles/pimcoreadmin/img/material-icons/outline-sms-24px.svg">
        <span id="notification_value" style="display:none;"></span>
    </div>

    <div id="pimcore_avatar" style="display:none;">
        <img src="<?=$view->router()->path('pimcore_admin_user_getimage')?>" data-menu-tooltip="<?= $user->getName() ?> | <?= $this->translate('my_profile') ?>"/>
    </div>
    <a id="pimcore_logout" data-menu-tooltip="<?= $this->translate("logout") ?>" href="<?= $view->router()->path('pimcore_admin_logout') ?>" style="display: none">
        <img src="/bundles/pimcoreadmin/img/material-icons/outline-logout-24px.svg">
    </a>
    <div id="pimcore_signet" data-menu-tooltip="Pimcore Platform (<?= \Pimcore\Version::getVersion() ?>|<?= \Pimcore\Version::getRevision() ?>)" style="text-indent: -10000px">
        BE RESPECTFUL AND HONOR OUR WORK FOR FREE & OPEN SOURCE SOFTWARE BY NOT REMOVING OUR LOGO.
        WE OFFER YOU THE POSSIBILITY TO ADDITIONALLY ADD YOUR OWN LOGO IN PIMCORE'S SYSTEM SETTINGS. THANK YOU!
    </div>
</div>

<div id="pimcore_tooltip" style="display: none;"></div>
<div id="pimcore_quicksearch"></div>

<?php // define stylesheets ?>
<?php

$disableMinifyJs = Pimcore::disableMinifyJs();

// SCRIPT LIBRARIES
$debugSuffix = "";
if ($disableMinifyJs) {
    $debugSuffix = "-debug";
}

$styles = array(
    $view->router()->path('pimcore_admin_misc_admincss'),
    "/bundles/pimcoreadmin/css/icons.css",
    "/bundles/pimcoreadmin/js/lib/leaflet/leaflet.css",
    "/bundles/pimcoreadmin/js/lib/leaflet.draw/leaflet.draw.css",
    "/bundles/pimcoreadmin/css/ext-js/PimcoreApp-all_1.css",
    "/bundles/pimcoreadmin/css/ext-js/PimcoreApp-all_2.css",
    "/bundles/pimcoreadmin/css/admin.css"
);
?>

<!-- stylesheets -->
<style type="text/css">
    <?php
    // use @import here, because if IE9 CSS file limitations (31 files)
    // see also: http://blogs.telerik.com/blogs/posts/10-05-03/internet-explorer-css-limits.aspx
    // @import bypasses this problem in an elegant way
    foreach ($styles as $style) { ?>
    @import url(<?= $style ?>?_dc=<?= \Pimcore\Version::getRevision() ?>);
    <?php } ?>
</style>


<?php //****************************************************************************************** ?>


<?php // define scripts ?>

<script type="text/javascript">
    var Ext = Ext || {};
    Ext.manifest = "/bundles/pimcoreadmin/js/pimcore.json";
    Ext.beforeLoad = function(platformTags) {
            console.log("BEFORE LOAD");

            // this is used in bootstrap.js
            Ext._customCachingParam = "?><?= \Pimcore\Version::getRevision();?>";
    } ;
</script>


<?php


$scriptLibs = array(

    // library
    "lib/class.js",
    "lib/ckeditor/ckeditor.js",
    "lib/leaflet/leaflet.js",
    "lib/leaflet.draw/leaflet.draw.js",
    "lib/vrview/build/vrview.min.js",
    "bootstrap.js"
);

?>

<!-- some javascript -->
<?php // pimcore constants ?>
<script>
    pimcore.settings = <?= json_encode($this->settings, JSON_PRETTY_PRINT) ?>;
</script>

<script src="<?= $view->router()->path('pimcore_admin_misc_jsontranslationssystem', ['language' => $language, '_dc' => \Pimcore\Version::getRevision()])?>"></script>
<script src="<?= $view->router()->path('pimcore_admin_user_getcurrentuser') ?>?_dc=<?= \Pimcore\Version::getRevision() ?>"></script>
<script src="<?= $view->router()->path('pimcore_admin_misc_availablelanguages', ['_dc' => \Pimcore\Version::getRevision()]) ?>"></script>

<?php

$pluginDcValue = time();
if ($disableMinifyJs) {
    $pluginDcValue = 1;
}

?>



<?php foreach ($this->pluginCssPaths as $pluginCssPath): ?>
    <link rel="stylesheet" type="text/css" href="<?= $pluginCssPath ?>?_dc=<?= $pluginDcValue; ?>"/>
<?php endforeach; ?>

<!-- library scripts -->
<?php foreach ($scriptLibs as $scriptUrl) { ?>
    <script src="/bundles/pimcoreadmin/js/<?= $scriptUrl ?>?_dc=<?= \Pimcore\Version::getRevision() ?>"></script>
<?php } ?>

</body>
</html>
