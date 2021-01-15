<?php
/** @var \Pimcore\Templating\PhpEngine $view */
/** @var \Pimcore\Templating\PhpEngine $this */
/** @var \Pimcore\Templating\GlobalVariables $app */
$app = $view->app;

$viewModel = $this->getViewModel();
$settings = $viewModel->get('settings');
$pluginJsPaths = $viewModel->get('pluginJsPaths');
$pluginCssPaths = $viewModel->get('pluginCssPaths');

$language = $app->getRequest()->getLocale();

/** @var \Pimcore\Templating\Helper\Translate $translator */
$translator = $this->get("translate");
$translator->setDomain("admin");

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
    "/bundles/pimcoreadmin/js/lib/ext/classic/theme-triton/resources/theme-triton-all.css",
    "/bundles/pimcoreadmin/js/lib/ext/classic/theme-triton/resources/charts-all" . $debugSuffix . ".css",
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
<?php


$scriptLibs = array(

    // library
    "lib/class.js",
    "lib/ext/ext-all" . $debugSuffix . ".js",
    "lib/ext/classic/theme-triton/theme-triton" . $debugSuffix . ".js",

    "lib/ext/packages/charts/classic/charts" . $debugSuffix . ".js",              // TODO

    "lib/ext-plugins/portlet/PortalDropZone.js",
    "lib/ext-plugins/portlet/Portlet.js",
    "lib/ext-plugins/portlet/PortalColumn.js",
    "lib/ext-plugins/portlet/PortalPanel.js",
    "lib/ext-plugins/TabMiddleButtonClose.js",

    "lib/ckeditor/ckeditor.js",

    "lib/leaflet/leaflet.js",
    "lib/leaflet.draw/leaflet.draw.js",
    "lib/vrview/build/vrview.min.js",

    // locale
    "lib/ext/classic/locale/locale-" . $language . ".js",
);

// PIMCORE SCRIPTS
$scripts = array(

    // runtime
    "pimcore/functions.js",
    "pimcore/common.js",
    "pimcore/elementservice.js",
    "pimcore/helpers.js",
    "pimcore/error.js",

    "pimcore/treenodelocator.js",
    "pimcore/helpers/generic-grid.js",
    "pimcore/helpers/quantityValue.js",
    "pimcore/overrides.js",

    "pimcore/perspective.js",
    "pimcore/user.js",

    // tools
    "pimcore/tool/paralleljobs.js",
    "pimcore/tool/genericiframewindow.js",

    // settings
    "pimcore/settings/user/panels/abstract.js",
    "pimcore/settings/user/panel.js",

    "pimcore/settings/user/usertab.js",
    "pimcore/settings/user/editorSettings.js",
    "pimcore/settings/user/websiteTranslationSettings.js",
    "pimcore/settings/user/role/panel.js",
    "pimcore/settings/user/role/tab.js",
    "pimcore/settings/user/user/objectrelations.js",
    "pimcore/settings/user/user/settings.js",
    "pimcore/settings/user/user/keyBindings.js",
    "pimcore/settings/user/workspaces.js",
    "pimcore/settings/user/workspace/asset.js",
    "pimcore/settings/user/workspace/document.js",
    "pimcore/settings/user/workspace/object.js",
    "pimcore/settings/user/workspace/customlayouts.js",
    "pimcore/settings/user/workspace/language.js",
    "pimcore/settings/user/workspace/special.js",
    "pimcore/settings/user/role/settings.js",
    "pimcore/settings/profile/panel.js",
    "pimcore/settings/profile/twoFactorSettings.js",
    "pimcore/settings/thumbnail/item.js",
    "pimcore/settings/thumbnail/panel.js",
    "pimcore/settings/videothumbnail/item.js",
    "pimcore/settings/videothumbnail/panel.js",
    "pimcore/settings/translations.js",
    "pimcore/settings/translationEditor.js",
    "pimcore/settings/translation/website.js",
    "pimcore/settings/translation/admin.js",
    "pimcore/settings/translation/translationmerger.js",
    "pimcore/settings/translation/xliff.js",
    "pimcore/settings/translation/word.js",
    "pimcore/settings/metadata/predefined.js",
    "pimcore/settings/properties/predefined.js",
    "pimcore/settings/docTypes.js",
    "pimcore/settings/system.js",
    "pimcore/settings/web2print.js",
    "pimcore/settings/website.js",
    "pimcore/settings/staticroutes.js",
    "pimcore/settings/redirects.js",
    "pimcore/settings/glossary.js",
    "pimcore/settings/recyclebin.js",
    "pimcore/settings/fileexplorer/file.js",
    "pimcore/settings/fileexplorer/explorer.js",
    "pimcore/settings/maintenance.js",
    "pimcore/settings/robotstxt.js",
    "pimcore/settings/httpErrorLog.js",
    "pimcore/settings/email/log.js",
    "pimcore/settings/email/blacklist.js",
    "pimcore/settings/targeting/condition/abstract.js",
    "pimcore/settings/targeting/conditions.js",
    "pimcore/settings/targeting/action/abstract.js",
    "pimcore/settings/targeting/actions.js",
    "pimcore/settings/targeting/rules/panel.js",
    "pimcore/settings/targeting/rules/item.js",
    "pimcore/settings/targeting/targetGroups/panel.js",
    "pimcore/settings/targeting/targetGroups/item.js",
    "pimcore/settings/targeting_toolbar.js",

    "pimcore/settings/gdpr/gdprPanel.js",
    "pimcore/settings/gdpr/dataproviders/assets.js",
    "pimcore/settings/gdpr/dataproviders/dataObjects.js",
    "pimcore/settings/gdpr/dataproviders/sentMail.js",
    "pimcore/settings/gdpr/dataproviders/pimcoreUsers.js",

    // element
    "pimcore/element/abstract.js",
    "pimcore/element/selector/selector.js",
    "pimcore/element/selector/abstract.js",
    "pimcore/element/selector/document.js",
    "pimcore/element/selector/asset.js",
    "pimcore/element/properties.js",
    "pimcore/element/scheduler.js",
    "pimcore/element/dependencies.js",
    "pimcore/element/metainfo.js",
    "pimcore/element/history.js",
    "pimcore/element/notes.js",
    "pimcore/element/note_details.js",
    "pimcore/element/workflows.js",
    "pimcore/element/tag/imagecropper.js",
    "pimcore/element/tag/imagehotspotmarkereditor.js",
    "pimcore/element/replace_assignments.js",
    "pimcore/element/permissionchecker.js",
    "pimcore/element/gridexport/abstract.js",
    "pimcore/element/helpers/gridColumnConfig.js",
    "pimcore/element/helpers/gridConfigDialog.js",
    "pimcore/element/helpers/gridCellEditor.js",
    "pimcore/element/helpers/gridTabAbstract.js",
    "pimcore/object/helpers/grid.js",
    "pimcore/object/helpers/gridConfigDialog.js",
    "pimcore/object/helpers/import/csvPreviewTab.js",
    "pimcore/object/helpers/import/columnConfigurationTab.js",
    "pimcore/object/helpers/import/resolverSettingsTab.js",
    "pimcore/object/helpers/import/csvSettingsTab.js",
    "pimcore/object/helpers/import/saveAndShareTab.js",
    "pimcore/object/helpers/import/configDialog.js",
    "pimcore/object/helpers/import/reportTab.js",
    "pimcore/object/helpers/classTree.js",
    "pimcore/object/helpers/gridTabAbstract.js",
    "pimcore/object/helpers/metadataMultiselectEditor.js",
    "pimcore/object/helpers/customLayoutEditor.js",
    "pimcore/object/helpers/optionEditor.js",
    "pimcore/object/helpers/imageGalleryDropZone.js",
    "pimcore/object/helpers/imageGalleryPanel.js",
    "pimcore/element/selector/object.js",
    "pimcore/element/tag/configuration.js",
    "pimcore/element/tag/assignment.js",
    "pimcore/element/tag/tree.js",
    "pimcore/asset/helpers/metadataTree.js",
    "pimcore/asset/helpers/gridConfigDialog.js",
    "pimcore/asset/helpers/gridTabAbstract.js",
    "pimcore/asset/helpers/grid.js",

    // documents
    "pimcore/document/properties.js",
    "pimcore/document/document.js",
    "pimcore/document/page_snippet.js",
    "pimcore/document/edit.js",
    "pimcore/document/versions.js",
    "pimcore/document/settings_abstract.js",
    "pimcore/document/pages/settings.js",
    "pimcore/document/pages/preview.js",
    "pimcore/document/snippets/settings.js",
    "pimcore/document/emails/settings.js",
    "pimcore/document/newsletters/settings.js",
    "pimcore/document/newsletters/sendingPanel.js",
    "pimcore/document/newsletters/plaintextPanel.js",
    "pimcore/document/newsletters/addressSourceAdapters/default.js",
    "pimcore/document/newsletters/addressSourceAdapters/csvList.js",
    "pimcore/document/newsletters/addressSourceAdapters/report.js",
    "pimcore/document/link.js",
    "pimcore/document/hardlink.js",
    "pimcore/document/folder.js",
    "pimcore/document/tree.js",
    "pimcore/document/snippet.js",
    "pimcore/document/email.js",
    "pimcore/document/newsletter.js",
    "pimcore/document/page.js",
    "pimcore/document/printpages/pdf_preview.js",
    "pimcore/document/printabstract.js",
    "pimcore/document/printpage.js",
    "pimcore/document/printcontainer.js",
    "pimcore/document/seopanel.js",
    "pimcore/document/document_language_overview.js",
    "pimcore/document/customviews/tree.js",

    // assets
    "pimcore/asset/metadata/data/data.js",
    "pimcore/asset/metadata/data/input.js",
    "pimcore/asset/metadata/data/textarea.js",
    "pimcore/asset/metadata/data/asset.js",
    "pimcore/asset/metadata/data/document.js",
    "pimcore/asset/metadata/data/object.js",
    "pimcore/asset/metadata/data/date.js",
    "pimcore/asset/metadata/data/checkbox.js",
    "pimcore/asset/metadata/data/select.js",

    "pimcore/asset/metadata/tags/abstract.js",
    "pimcore/asset/metadata/tags/checkbox.js",
    "pimcore/asset/metadata/tags/date.js",
    "pimcore/asset/metadata/tags/input.js",
    "pimcore/asset/metadata/tags/manyToOneRelation.js",
    "pimcore/asset/metadata/tags/asset.js",
    "pimcore/asset/metadata/tags/document.js",
    "pimcore/asset/metadata/tags/object.js",
    "pimcore/asset/metadata/tags/select.js",
    "pimcore/asset/metadata/tags/textarea.js",
    "pimcore/asset/asset.js",
    "pimcore/asset/unknown.js",
    "pimcore/asset/embedded_meta_data.js",
    "pimcore/asset/image.js",
    "pimcore/asset/document.js",
    "pimcore/asset/video.js",
    "pimcore/asset/audio.js",
    "pimcore/asset/text.js",
    "pimcore/asset/folder.js",
    "pimcore/asset/listfolder.js",
    "pimcore/asset/versions.js",
    "pimcore/asset/metadata/dataProvider.js",
    "pimcore/asset/metadata/grid.js",
    "pimcore/asset/metadata/editor.js",
    "pimcore/asset/tree.js",
    "pimcore/asset/customviews/tree.js",
    "pimcore/asset/gridexport/xlsx.js",
    "pimcore/asset/gridexport/csv.js",

    // object
    "pimcore/object/helpers/edit.js",
    "pimcore/object/helpers/layout.js",
    "pimcore/object/classes/class.js",
    "pimcore/object/class.js",
    "pimcore/object/bulk-base.js",
    "pimcore/object/bulk-export.js",
    "pimcore/object/bulk-import.js",
    "pimcore/object/classes/data/data.js",          // THIS MUST BE THE FIRST FILE, DO NOT MOVE THIS DOWN !!!
    "pimcore/object/classes/data/block.js",
    "pimcore/object/classes/data/classificationstore.js",
    "pimcore/object/classes/data/rgbaColor.js",
    "pimcore/object/classes/data/date.js",
    "pimcore/object/classes/data/datetime.js",
    "pimcore/object/classes/data/encryptedField.js",
    "pimcore/object/classes/data/time.js",
    "pimcore/object/classes/data/manyToOneRelation.js",
    "pimcore/object/classes/data/image.js",
    "pimcore/object/classes/data/externalImage.js",
    "pimcore/object/classes/data/hotspotimage.js",
    "pimcore/object/classes/data/imagegallery.js",
    "pimcore/object/classes/data/video.js",
    "pimcore/object/classes/data/input.js",
    "pimcore/object/classes/data/numeric.js",
    "pimcore/object/classes/data/manyToManyObjectRelation.js",
    "pimcore/object/classes/data/advancedManyToManyRelation.js",
    "pimcore/object/classes/data/advancedManyToManyObjectRelation.js",
    "pimcore/object/classes/data/reverseManyToManyObjectRelation.js",
    "pimcore/object/classes/data/booleanSelect.js",
    "pimcore/object/classes/data/select.js",
    "pimcore/object/classes/data/urlSlug.js",
    "pimcore/object/classes/data/user.js",
    "pimcore/object/classes/data/textarea.js",
    "pimcore/object/classes/data/wysiwyg.js",
    "pimcore/object/classes/data/checkbox.js",
    "pimcore/object/classes/data/consent.js",
    "pimcore/object/classes/data/slider.js",
    "pimcore/object/classes/data/manyToManyRelation.js",
    "pimcore/object/classes/data/table.js",
    "pimcore/object/classes/data/structuredTable.js",
    "pimcore/object/classes/data/country.js",
    "pimcore/object/classes/data/geo/abstract.js",
    "pimcore/object/classes/data/geopoint.js",
    "pimcore/object/classes/data/geobounds.js",
    "pimcore/object/classes/data/geopolygon.js",
    "pimcore/object/classes/data/geopolyline.js",
    "pimcore/object/classes/data/language.js",
    "pimcore/object/classes/data/password.js",
    "pimcore/object/classes/data/multiselect.js",
    "pimcore/object/classes/data/link.js",
    "pimcore/object/classes/data/fieldcollections.js",
    "pimcore/object/classes/data/objectbricks.js",
    "pimcore/object/classes/data/localizedfields.js",
    "pimcore/object/classes/data/countrymultiselect.js",
    "pimcore/object/classes/data/languagemultiselect.js",
    "pimcore/object/classes/data/firstname.js",
    "pimcore/object/classes/data/lastname.js",
    "pimcore/object/classes/data/email.js",
    "pimcore/object/classes/data/gender.js",
    "pimcore/object/classes/data/newsletterActive.js",
    "pimcore/object/classes/data/newsletterConfirmed.js",
    "pimcore/object/classes/data/targetGroup.js",
    "pimcore/object/classes/data/targetGroupMultiselect.js",
    "pimcore/object/classes/data/quantityValue.js",
    "pimcore/object/classes/data/inputQuantityValue.js",
    "pimcore/object/classes/data/calculatedValue.js",
    "pimcore/object/classes/layout/layout.js",
    "pimcore/object/classes/layout/accordion.js",
    "pimcore/object/classes/layout/fieldset.js",
    "pimcore/object/classes/layout/fieldcontainer.js",
    "pimcore/object/classes/layout/panel.js",
    "pimcore/object/classes/layout/region.js",
    "pimcore/object/classes/layout/tabpanel.js",
    "pimcore/object/classes/layout/button.js",
    "pimcore/object/classes/layout/iframe.js",
    "pimcore/object/fieldlookup/filterdialog.js",
    "pimcore/object/fieldlookup/helper.js",
    "pimcore/object/classes/layout/text.js",
    "pimcore/object/fieldcollection.js",
    "pimcore/object/fieldcollections/field.js",
    "pimcore/object/gridcolumn/Abstract.js",
    "pimcore/object/gridcolumn/operator/IsEqual.js",
    "pimcore/object/gridcolumn/operator/Text.js",
    "pimcore/object/gridcolumn/operator/Anonymizer.js",
    "pimcore/object/gridcolumn/operator/AnyGetter.js",
    "pimcore/object/gridcolumn/operator/AssetMetadataGetter.js",
    "pimcore/object/gridcolumn/operator/Arithmetic.js",
    "pimcore/object/gridcolumn/operator/Boolean.js",
    "pimcore/object/gridcolumn/operator/BooleanFormatter.js",
    "pimcore/object/gridcolumn/operator/CaseConverter.js",
    "pimcore/object/gridcolumn/operator/CharCounter.js",
    "pimcore/object/gridcolumn/operator/Concatenator.js",
    "pimcore/object/gridcolumn/operator/DateFormatter.js",
    "pimcore/object/gridcolumn/operator/ElementCounter.js",
    "pimcore/object/gridcolumn/operator/Iterator.js",
    "pimcore/object/gridcolumn/operator/JSON.js",
    "pimcore/object/gridcolumn/operator/LocaleSwitcher.js",
    "pimcore/object/gridcolumn/operator/Merge.js",
    "pimcore/object/gridcolumn/operator/ObjectFieldGetter.js",
    "pimcore/object/gridcolumn/operator/PHP.js",
    "pimcore/object/gridcolumn/operator/PHPCode.js",
    "pimcore/object/gridcolumn/operator/Base64.js",
    "pimcore/object/gridcolumn/operator/TranslateValue.js",
    "pimcore/object/gridcolumn/operator/PropertyGetter.js",
    "pimcore/object/gridcolumn/operator/RequiredBy.js",
    "pimcore/object/gridcolumn/operator/StringContains.js",
    "pimcore/object/gridcolumn/operator/StringReplace.js",
    "pimcore/object/gridcolumn/operator/Substring.js",
    "pimcore/object/gridcolumn/operator/LFExpander.js",
    "pimcore/object/gridcolumn/operator/Trimmer.js",
    "pimcore/object/gridcolumn/operator/Alias.js",
    "pimcore/object/gridcolumn/operator/WorkflowState.js",
    "pimcore/object/gridcolumn/value/DefaultValue.js",
    "pimcore/object/gridcolumn/operator/GeopointRenderer.js",
    "pimcore/object/gridcolumn/operator/ImageRenderer.js",
    "pimcore/object/gridcolumn/operator/HotspotimageRenderer.js",
    "pimcore/object/importcolumn/Abstract.js",
    "pimcore/object/importcolumn/operator/Base64.js",
    "pimcore/object/importcolumn/operator/Ignore.js",
    "pimcore/object/importcolumn/operator/Iterator.js",
    "pimcore/object/importcolumn/operator/LocaleSwitcher.js",
    "pimcore/object/importcolumn/operator/ObjectBrickSetter.js",
    "pimcore/object/importcolumn/operator/PHPCode.js",
    "pimcore/object/importcolumn/operator/Published.js",
    "pimcore/object/importcolumn/operator/Splitter.js",
    "pimcore/object/importcolumn/operator/Unserialize.js",
    "pimcore/object/importcolumn/value/DefaultValue.js",
    "pimcore/object/objectbrick.js",
    "pimcore/object/objectbricks/field.js",
    "pimcore/object/tags/abstract.js",
    "pimcore/object/tags/abstractRelations.js",
    "pimcore/object/tags/block.js",
    "pimcore/object/tags/rgbaColor.js",
    "pimcore/object/tags/date.js",
    "pimcore/object/tags/datetime.js",
    "pimcore/object/tags/time.js",
    "pimcore/object/tags/manyToOneRelation.js",
    "pimcore/object/tags/image.js",
    "pimcore/object/tags/encryptedField.js",
    "pimcore/object/tags/externalImage.js",
    "pimcore/object/tags/hotspotimage.js",
    "pimcore/object/tags/imagegallery.js",
    "pimcore/object/tags/video.js",
    "pimcore/object/tags/input.js",
    "pimcore/object/tags/classificationstore.js",
    "pimcore/object/tags/numeric.js",
    "pimcore/object/tags/manyToManyObjectRelation.js",
    "pimcore/object/tags/advancedManyToManyRelation.js",
    "pimcore/object/gridcolumn/operator/FieldCollectionGetter.js",
    "pimcore/object/gridcolumn/operator/ObjectBrickGetter.js",
    "pimcore/object/tags/advancedManyToManyObjectRelation.js",
    "pimcore/object/tags/reverseManyToManyObjectRelation.js",
    "pimcore/object/tags/urlSlug.js",
    "pimcore/object/tags/booleanSelect.js",
    "pimcore/object/tags/select.js",
    "pimcore/object/tags/user.js",
    "pimcore/object/tags/checkbox.js",
    "pimcore/object/tags/consent.js",
    "pimcore/object/tags/textarea.js",
    "pimcore/object/tags/wysiwyg.js",
    "pimcore/object/tags/slider.js",
    "pimcore/object/tags/manyToManyRelation.js",
    "pimcore/object/tags/table.js",
    "pimcore/object/tags/structuredTable.js",
    "pimcore/object/tags/country.js",
    "pimcore/object/tags/geo/abstract.js",
    "pimcore/object/tags/geobounds.js",
    "pimcore/object/tags/geopoint.js",
    "pimcore/object/tags/geopolygon.js",
    "pimcore/object/tags/geopolyline.js",
    "pimcore/object/tags/language.js",
    "pimcore/object/tags/password.js",
    "pimcore/object/tags/multiselect.js",
    "pimcore/object/tags/link.js",
    "pimcore/object/tags/fieldcollections.js",
    "pimcore/object/tags/localizedfields.js",
    "pimcore/object/tags/countrymultiselect.js",
    "pimcore/object/tags/languagemultiselect.js",
    "pimcore/object/tags/objectbricks.js",
    "pimcore/object/tags/firstname.js",
    "pimcore/object/tags/lastname.js",
    "pimcore/object/tags/email.js",
    "pimcore/object/tags/gender.js",
    "pimcore/object/tags/newsletterActive.js",
    "pimcore/object/tags/newsletterConfirmed.js",
    "pimcore/object/tags/targetGroup.js",
    "pimcore/object/tags/targetGroupMultiselect.js",
    "pimcore/object/tags/quantityValue.js",
    "pimcore/object/tags/inputQuantityValue.js",
    "pimcore/object/tags/calculatedValue.js",
    "pimcore/object/preview.js",
    "pimcore/object/versions.js",
    "pimcore/object/variantsTab.js",
    "pimcore/object/folder/search.js",
    "pimcore/object/edit.js",
    "pimcore/object/abstract.js",
    "pimcore/object/object.js",
    "pimcore/object/folder.js",
    "pimcore/object/variant.js",
    "pimcore/object/tree.js",
    "pimcore/object/layout/iframe.js",
    "pimcore/object/customviews/tree.js",
    "pimcore/object/quantityvalue/unitsettings.js",
    "pimcore/object/gridexport/xlsx.js",
    "pimcore/object/gridexport/csv.js",

    //plugins
    "pimcore/plugin/broker.js",
    "pimcore/plugin/plugin.js",

    "pimcore/event-dispatcher.js",

    // reports
    "pimcore/report/panel.js",
    "pimcore/report/broker.js",
    "pimcore/report/abstract.js",
    "pimcore/report/settings.js",
    "pimcore/report/analytics/settings.js",
    "pimcore/report/analytics/elementoverview.js",
    "pimcore/report/analytics/elementexplorer.js",
    "pimcore/report/webmastertools/settings.js",
    "pimcore/report/tagmanager/settings.js",
    "pimcore/report/custom/item.js",
    "pimcore/report/custom/panel.js",
    "pimcore/report/custom/settings.js",
    "pimcore/report/custom/report.js",
    "pimcore/report/custom/definitions/sql.js",
    "pimcore/report/custom/definitions/analytics.js",
    "pimcore/report/custom/toolbarenricher.js",

    "pimcore/settings/tagmanagement/panel.js",
    "pimcore/settings/tagmanagement/item.js",

    "pimcore/report/qrcode/panel.js",
    "pimcore/report/qrcode/item.js",

    // extension manager
    "pimcore/extensionmanager/admin.js",

    // application logging
    "pimcore/log/admin.js",
    "pimcore/log/detailwindow.js",

    // layout
    "pimcore/layout/portal.js",
    "pimcore/layout/portlets/abstract.js",
    "pimcore/layout/portlets/modifiedDocuments.js",
    "pimcore/layout/portlets/modifiedObjects.js",
    "pimcore/layout/portlets/modifiedAssets.js",
    "pimcore/layout/portlets/modificationStatistic.js",
    "pimcore/layout/portlets/analytics.js",
    "pimcore/layout/portlets/piwik.js",
    "pimcore/layout/portlets/customreports.js",

    "pimcore/layout/toolbar.js",
    "pimcore/layout/treepanelmanager.js",
    "pimcore/document/seemode.js",

    // classification store
    "pimcore/object/classificationstore/groupsPanel.js",
    "pimcore/object/classificationstore/propertiesPanel.js",
    "pimcore/object/classificationstore/collectionsPanel.js",
    "pimcore/object/classificationstore/keyDefinitionWindow.js",
    "pimcore/object/classificationstore/keySelectionWindow.js",
    "pimcore/object/classificationstore/relationSelectionWindow.js",
    "pimcore/object/classificationstore/storeConfiguration.js",
    "pimcore/object/classificationstore/storeTree.js",
    "pimcore/object/classificationstore/columnConfigDialog.js",

    //workflow
    "pimcore/workflow/transitionPanel.js",
    "pimcore/workflow/transitions.js",

    // Piwik - this needs to be loaded after treepanel manager as
    // it adds panels in pimcore ready
    "pimcore/analytics/piwik/widget_store_provider.js",
    "pimcore/report/piwik/settings.js",
    "pimcore/report/piwik/dashboard_iframe.js",

    // color picker
    "pimcore/colorpicker-overrides.js",

    //notification
    "pimcore/notification/helper.js",
    "pimcore/notification/panel.js",
    "pimcore/notification/modal.js",
);

?>

<!-- some javascript -->
<?php // pimcore constants ?>
<script>
    pimcore.settings = <?= json_encode($settings, JSON_PRETTY_PRINT) ?>;
</script>

<script src="<?= $view->router()->path('pimcore_admin_misc_jsontranslationssystem', ['language' => $language, '_dc' => \Pimcore\Version::getRevision()])?>"></script>
<script src="<?= $view->router()->path('pimcore_admin_user_getcurrentuser') ?>?_dc=<?= \Pimcore\Version::getRevision() ?>"></script>
<script src="<?= $view->router()->path('pimcore_admin_misc_availablelanguages', ['_dc' => \Pimcore\Version::getRevision()]) ?>"></script>


<!-- library scripts -->
<?php foreach ($scriptLibs as $scriptUrl) { ?>
    <script src="/bundles/pimcoreadmin/js/<?= $scriptUrl ?>?_dc=<?= \Pimcore\Version::getRevision() ?>"></script>
<?php } ?>


<!-- internal scripts -->
<?php if ($disableMinifyJs) { ?>
    <?php foreach ($scripts as $scriptUrl) { ?>
    <script src="/bundles/pimcoreadmin/js/<?= $scriptUrl ?>?_dc=<?= \Pimcore\Version::getRevision() ?>"></script>
<?php } ?>
<?php } else { ?>
<?php

    $scriptContents = "";
    foreach ($scripts as $scriptUrl) {
        if (is_file(PIMCORE_WEB_ROOT . "/bundles/pimcoreadmin/js/" . $scriptUrl)) {
            $scriptContents .= file_get_contents(PIMCORE_WEB_ROOT . "/bundles/pimcoreadmin/js/" . $scriptUrl) . "\n\n\n";
        }
    }
    $minimizedScriptParams = \Pimcore\Tool\Admin::getMinimizedScriptPath($scriptContents, false);

?>
    <script src="<?= $this->router()->path('pimcore_admin_misc_scriptproxy', $minimizedScriptParams) ?>"></script>
<?php } ?>


<?php // load plugin scripts ?>
<?php

// only add the timestamp if the devmode is not activated, otherwise it is very hard to develop and debug plugins,
// because the filename changes on every reload and therefore breakpoints, ... are resetted on every reload
$pluginDcValue = time();
if ($disableMinifyJs) {
    $pluginDcValue = 1;
}
?>

<?php foreach ($pluginJsPaths as $pluginJsPath): ?>
    <script src="<?= $pluginJsPath ?>?_dc=<?= $pluginDcValue; ?>"></script>
<?php endforeach; ?>

<?php foreach ($pluginCssPaths as $pluginCssPath): ?>
    <link rel="stylesheet" type="text/css" href="<?= $pluginCssPath ?>?_dc=<?= $pluginDcValue; ?>"/>
<?php endforeach; ?>

<?php // MUST BE THE LAST LINE ?>
<script src="/bundles/pimcoreadmin/js/pimcore/startup.js?_dc=<?= \Pimcore\Version::getRevision() ?>"></script>
</body>
</html>
