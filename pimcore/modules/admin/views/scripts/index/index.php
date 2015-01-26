<!DOCTYPE html>
<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="apple-mobile-web-app-capable" content="yes" />

    <link rel="icon" type="image/png" href="/pimcore/static/img/favicon/favicon-32x32.png" />

    <style type="text/css">
        body {
            /* this stops the loading indicator from hopping around */
            margin: 0;
            padding: 0;
        }
    </style>

    <title><?php echo htmlentities($this->getRequest()->getHttpHost(), ENT_QUOTES, 'UTF-8') ?> :: pimcore</title>

    <!-- load in head because of the progress bar at loading -->
    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/admin.css?_dc=<?php echo \Pimcore\Version::$revision ?>" />
</head>

<body>

<div id="pimcore_logo" style="display: none;">
    <img src="/pimcore/static/img/logo.png"/>
</div>

<div id="pimcore_loading">
    <img class="loading" src="/pimcore/static/img/loading-white-bg.gif?_dc=<?php echo \Pimcore\Version::$revision ?>" />
</div>

<div id="pimcore_navigation" style="display:none;">
    <ul>
        <li id="pimcore_menu_avatar" class="pimcore_menu_avatar">
            <img src="/admin/user/get-image" />
        </li>
        <li id="pimcore_menu_file" class="pimcore_menu_item icon-th-large"><?php echo $this->translate("file"); ?></li>
        <li id="pimcore_menu_extras" class="pimcore_menu_item icon-rocket pimcore_menu_needs_children"><?php echo $this->translate("extras"); ?></li>
        <li id="pimcore_menu_marketing" class="pimcore_menu_item icon-chart-bar pimcore_menu_needs_children"><?php echo $this->translate("marketing"); ?></li>
        <li id="pimcore_menu_settings" class="pimcore_menu_item icon-cog-alt pimcore_menu_needs_children"><?php echo $this->translate("settings"); ?></li>
        <li id="pimcore_menu_maintenance" class="pimcore_menu_item icon-hammer" style="display:none;"><?php echo $this->translate("deactivate_maintenance"); ?></li>
        <li id="pimcore_menu_search" class="pimcore_menu_item icon-search pimcore_menu_needs_children"><?php echo $this->translate("search"); ?></li>
        <li id="pimcore_menu_logout" class="pimcore_menu_item icon-logout"><?php echo $this->translate("logout"); ?></li>
    </ul>
</div>


<script type="text/javascript">
    var pimcore = {}; // namespace
</script>


<?php // define stylesheets ?>
<?php
$styles = array(
    "/admin/misc/admin-css",
    "/pimcore/static/css/icons.css",
    "/pimcore/static/js/lib/ext/resources/css/ext-all.css",
    "/pimcore/static/js/lib/ext/resources/css/xtheme-gray.css",
    "/pimcore/static/js/lib/ext-plugins/Notification/notification.css",
    "/pimcore/static/js/lib/ext-plugins/SuperBoxSelect/superboxselect.css",
    "/pimcore/static/js/lib/ext-plugins/ux/css/RowEditor.css",
    "/pimcore/static/js/lib/ext-plugins/ux/css/Spinner.css",
    "/pimcore/static/js/lib/ext-plugins/ux/statusbar/css/statusbar.css",
    "/pimcore/static/js/lib/ext-plugins/ux/css/Portal.css",
    "/pimcore/static/js/lib/ext-plugins/ux/css/MultiSelect.css",
    "/pimcore/static/js/lib/ext-plugins/ux/treegrid/treegrid.css",
    "/pimcore/static/js/lib/ext-plugins/ux/css/ColumnHeaderGroup.css",
    "/pimcore/static/js/lib/ext-plugins/ux/gridfilters/css/GridFilters.css",
    "/pimcore/static/js/lib/ext-plugins/ux/gridfilters/css/RangeMenu.css",
    "/pimcore/static/js/lib/ext-plugins/ux/fileuploadfield/css/fileuploadfield.css",
    "/pimcore/static/css/ext-admin-overwrite.css",
    "/pimcore/static/css/fontello.css"
);
?>

<!-- stylesheets -->
<style type="text/css">
    <?php
    // use @import here, because if IE9 CSS file limitations (31 files)
    // see also: http://blogs.telerik.com/blogs/posts/10-05-03/internet-explorer-css-limits.aspx
    // @import bypasses this problem in an elegant way
    foreach ($styles as $style) { ?>
    @import url(<?php echo $style ?>?_dc=<?php echo \Pimcore\Version::$revision ?>);
    <?php } ?>
</style>




<?php //****************************************************************************************** ?>





<?php // define scripts ?>
<?php

// SCRIPT LIBRARIES
$scriptExtAdapter = "lib/ext/adapter/jquery/ext-jquery-adapter.js";
$scriptExt = "lib/ext/ext-all.js";
if (PIMCORE_DEVMODE) {
    $scriptExtAdapter = "lib/ext/adapter/jquery/ext-jquery-adapter-debug.js";
    $scriptExt = "lib/ext/ext-all-debug.js";
}

$scriptLibs = array(

    // library
    "lib/prototype-light.js",
    "lib/jquery.min.js",
    $scriptExtAdapter,
    $scriptExt,

    "lib/ext-plugins/Notification/Ext.ux.Notification.js",
    "lib/ext-plugins/PagingTreeLoader/PagingTreeLoader.js",
    "lib/ext-plugins/GridRowOrder/roworder.js",
    "lib/ext-plugins/PimcoreFormLayout/panel.js",
    "lib/ext-plugins/ux/Reorderer.js",
    "lib/ext-plugins/ux/ColumnHeaderGroup.js",
    "lib/ext-plugins/ux/ToolbarReorderer.js",
    "lib/ext-plugins/ux/DataViewTransition.js",
    "lib/ext-plugins/ux/treegrid/TreeGridSorter.js",
    "lib/ext-plugins/ux/treegrid/TreeGridColumnResizer.js",
    "lib/ext-plugins/ux/treegrid/TreeGridNodeUI.js",
    "lib/ext-plugins/ux/treegrid/TreeGridLoader.js",
    "lib/ext-plugins/ux/treegrid/TreeGridColumns.js",
    "lib/ext-plugins/ux/treegrid/TreeGrid.js",
    "lib/ext-plugins/SuperBoxSelect/SuperBoxSelect.js",

    "lib/ext-plugins/ux/RowEditor.js",
    "lib/ext-plugins/ux/Spinner.js",
    "lib/ext-plugins/ux/SpinnerField.js",
    "lib/ext-plugins/ux/MultiSelect.js",
    "lib/ext-plugins/ux/CheckColumn.js",
    "lib/ext-plugins/ux/statusbar/StatusBar.js",
    "lib/ext-plugins/ux/Portal.js",
    "lib/ext-plugins/ux/PortalColumn.js",
    "lib/ext-plugins/ux/Portlet.js",
    "lib/ext-plugins/ux/gridfilters/menu/RangeMenu.js",
    "lib/ext-plugins/ux/gridfilters/menu/ListMenu.js",
    "lib/ext-plugins/ux/gridfilters/GridFilters.js",
    "lib/ext-plugins/ux/gridfilters/filter/Filter.js",
    "lib/ext-plugins/ux/gridfilters/filter/StringFilter.js",
    "lib/ext-plugins/ux/gridfilters/filter/DateFilter.js",
    "lib/ext-plugins/ux/gridfilters/filter/ListFilter.js",
    "lib/ext-plugins/ux/gridfilters/filter/NumericFilter.js",
    "lib/ext-plugins/ux/gridfilters/filter/BooleanFilter.js",
    "lib/ext-plugins/ux/fileuploadfield/FileUploadField.js",
    "lib/ckeditor/ckeditor.js",

    // locale
    "lib/ext/locale/ext-lang-" . $this->language . ".js",
);

// PIMCORE SCRIPTS
$scripts = array(

    // fixes for browsers
    "pimcore/browserfixes.js",

    // fixes for libraries
    "pimcore/libfixes.js",

    // small libs
    "lib/array_merge.js",
    "lib/array_merge_recursive.js",

    // runtime
    "pimcore/namespace.js",
    "pimcore/functions.js",
    "pimcore/globalmanager.js",
    "pimcore/helpers.js",

    "pimcore/user.js",

    // tools
    "pimcore/tool/paralleljobs.js",
    "pimcore/tool/genericiframewindow.js",

    // settings
    "pimcore/settings/user/panels/abstract.js",
    "pimcore/settings/user/panel.js",
    "pimcore/settings/user/usertab.js",
    "pimcore/settings/user/role/panel.js",
    "pimcore/settings/user/role/tab.js",
    "pimcore/settings/user/user/objectrelations.js",
    "pimcore/settings/user/user/settings.js",
    "pimcore/settings/user/workspaces.js",
    "pimcore/settings/user/workspace/asset.js",
    "pimcore/settings/user/workspace/document.js",
    "pimcore/settings/user/workspace/object.js",
    "pimcore/settings/user/workspace/customlayouts.js",
    "pimcore/settings/user/workspace/language.js",
    "pimcore/settings/user/workspace/special.js",
    "pimcore/settings/user/role/settings.js",
    "pimcore/settings/profile/panel.js",
    "pimcore/settings/thumbnail/item.js",
    "pimcore/settings/thumbnail/panel.js",
    "pimcore/settings/videothumbnail/item.js",
    "pimcore/settings/videothumbnail/panel.js",
    "pimcore/settings/translations.js",
    "pimcore/settings/translation/website.js",
    "pimcore/settings/translation/admin.js",
    "pimcore/settings/translation/translationmerger.js",
    "pimcore/settings/translation/xliff.js",
    "pimcore/settings/translation/word.js",
    "pimcore/settings/metadata/predefined.js",
    "pimcore/settings/properties/predefined.js",
    "pimcore/settings/docTypes.js",
    "pimcore/settings/system.js",
    "pimcore/settings/website.js",
    "pimcore/settings/staticroutes.js",
    "pimcore/settings/update.js",
    "pimcore/settings/languages.js",
    "pimcore/settings/redirects.js",
    "pimcore/settings/glossary.js",
    "pimcore/settings/backup.js",
    "pimcore/settings/recyclebin.js",
    "pimcore/settings/fileexplorer/file.js",
    "pimcore/settings/fileexplorer/explorer.js",
    "pimcore/settings/maintenance.js",
    "pimcore/settings/robotstxt.js",
    "pimcore/settings/httpErrorLog.js",
    "pimcore/settings/bouncemailinbox.js",
    "pimcore/settings/email/log.js",
    "pimcore/settings/email/blacklist.js",
    "pimcore/settings/targeting/conditions.js",
    "pimcore/settings/targeting/rules/panel.js",
    "pimcore/settings/targeting/rules/item.js",
    "pimcore/settings/targeting/personas/panel.js",
    "pimcore/settings/targeting/personas/item.js",

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
    "pimcore/element/tag/imagecropper.js",
    "pimcore/element/tag/imagehotspotmarkereditor.js",
    "pimcore/element/replace_assignments.js",
    "pimcore/object/helpers/grid.js",
    "pimcore/object/helpers/gridConfigDialog.js",
    "pimcore/object/helpers/classTree.js",
    "pimcore/object/helpers/gridTabAbstract.js",
    "pimcore/object/helpers/customLayoutEditor.js",
    "pimcore/element/selector/object.js",

    // documents
    "pimcore/document/properties.js",
    "pimcore/document/document.js",
    "pimcore/document/page_snippet.js",
    "pimcore/document/edit.js",
    "pimcore/document/versions.js",
    "pimcore/document/pages/settings.js",
    "pimcore/document/pages/preview.js",
    "pimcore/document/snippets/settings.js",
    "pimcore/document/emails/settings.js",
    "pimcore/document/link.js",
    "pimcore/document/hardlink.js",
    "pimcore/document/folder.js",
    "pimcore/document/tree.js",
    "pimcore/document/snippet.js",
    "pimcore/document/email.js",
    "pimcore/document/page.js",
    "pimcore/document/seopanel.js",

    // assets
    "pimcore/asset/asset.js",
    "pimcore/asset/unknown.js",
    "pimcore/asset/image.js",
    "pimcore/asset/document.js",
    "pimcore/asset/video.js",
    "pimcore/asset/text.js",
    "pimcore/asset/folder.js",
    "pimcore/asset/listfolder.js",
    "pimcore/asset/versions.js",
    "pimcore/asset/metadata.js",
    "pimcore/asset/tree.js",

    // object
    "pimcore/object/helpers/edit.js",
    "pimcore/object/classes/class.js",
    "pimcore/object/class.js",
    "pimcore/object/bulk-export.js",
    "pimcore/object/bulk-import.js",
    "pimcore/object/classes/data/data.js",
    "pimcore/object/classes/data/date.js",
    "pimcore/object/classes/data/datetime.js",
    "pimcore/object/classes/data/time.js",
    "pimcore/object/classes/data/href.js",
    "pimcore/object/classes/data/image.js",
    "pimcore/object/classes/data/hotspotimage.js",
    "pimcore/object/classes/data/video.js",
    "pimcore/object/classes/data/input.js",
    "pimcore/object/classes/data/numeric.js",
    "pimcore/object/classes/data/objects.js",
    "pimcore/object/classes/data/objectsMetadata.js",
    "pimcore/object/classes/data/nonownerobjects.js",
    "pimcore/object/classes/data/select.js",
    "pimcore/object/classes/data/user.js",
    "pimcore/object/classes/data/textarea.js",
    "pimcore/object/classes/data/wysiwyg.js",
    "pimcore/object/classes/data/checkbox.js",
    "pimcore/object/classes/data/slider.js",
    "pimcore/object/classes/data/multihref.js",
    "pimcore/object/classes/data/table.js",
    "pimcore/object/classes/data/structuredTable.js",
    "pimcore/object/classes/data/country.js",
    "pimcore/object/classes/data/geo/abstract.js",
    "pimcore/object/classes/data/geopoint.js",
    "pimcore/object/classes/data/geobounds.js",
    "pimcore/object/classes/data/geopolygon.js",
    "pimcore/object/classes/data/language.js",
    "pimcore/object/classes/data/password.js",
    "pimcore/object/classes/data/multiselect.js",
    "pimcore/object/classes/data/link.js",
    "pimcore/object/classes/data/fieldcollections.js",
    "pimcore/object/classes/data/objectbricks.js",
    "pimcore/object/classes/data/localizedfields.js",
    "pimcore/object/classes/data/countrymultiselect.js",
    "pimcore/object/classes/data/languagemultiselect.js",
    "pimcore/object/classes/data/keyValue.js",
    "pimcore/object/classes/data/firstname.js",
    "pimcore/object/classes/data/lastname.js",
    "pimcore/object/classes/data/email.js",
    "pimcore/object/classes/data/gender.js",
    "pimcore/object/classes/data/newsletterActive.js",
    "pimcore/object/classes/data/newsletterConfirmed.js",
    "pimcore/object/classes/data/persona.js",
    "pimcore/object/classes/data/personamultiselect.js",
    "pimcore/object/classes/layout/layout.js",
    "pimcore/object/classes/layout/accordion.js",
    "pimcore/object/classes/layout/fieldset.js",
    "pimcore/object/classes/layout/panel.js",
    "pimcore/object/classes/layout/region.js",
    "pimcore/object/classes/layout/tabpanel.js",
    "pimcore/object/classes/layout/button.js",
    "pimcore/object/classes/layout/text.js",
    "pimcore/object/fieldcollection.js",
    "pimcore/object/fieldcollections/field.js",
    "pimcore/object/objectbrick.js",
    "pimcore/object/objectbricks/field.js",
    "pimcore/object/tags/abstract.js",
    "pimcore/object/tags/date.js",
    "pimcore/object/tags/datetime.js",
    "pimcore/object/tags/time.js",
    "pimcore/object/tags/href.js",
    "pimcore/object/tags/image.js",
    "pimcore/object/tags/hotspotimage.js",
    "pimcore/object/tags/video.js",
    "pimcore/object/tags/input.js",
    "pimcore/object/tags/numeric.js",
    "pimcore/object/tags/objects.js",
    "pimcore/object/tags/objectsMetadata.js",
    "pimcore/object/tags/nonownerobjects.js",
    "pimcore/object/tags/select.js",
    "pimcore/object/tags/user.js",
    "pimcore/object/tags/checkbox.js",
    "pimcore/object/tags/textarea.js",
    "pimcore/object/tags/wysiwyg.js",
    "pimcore/object/tags/slider.js",
    "pimcore/object/tags/multihref.js",
    "pimcore/object/tags/table.js",
    "pimcore/object/tags/structuredTable.js",
    "pimcore/object/tags/country.js",
    "pimcore/object/tags/geo/abstract.js",
    "pimcore/object/tags/geobounds.js",
    "pimcore/object/tags/geopoint.js",
    "pimcore/object/tags/geopolygon.js",
    "pimcore/object/tags/language.js",
    "pimcore/object/tags/password.js",
    "pimcore/object/tags/multiselect.js",
    "pimcore/object/tags/link.js",
    "pimcore/object/tags/fieldcollections.js",
    "pimcore/object/tags/localizedfields.js",
    "pimcore/object/tags/countrymultiselect.js",
    "pimcore/object/tags/languagemultiselect.js",
    "pimcore/object/tags/objectbricks.js",
    "pimcore/object/tags/keyValue.js",
    "pimcore/object/tags/firstname.js",
    "pimcore/object/tags/lastname.js",
    "pimcore/object/tags/email.js",
    "pimcore/object/tags/gender.js",
    "pimcore/object/tags/newsletterActive.js",
    "pimcore/object/tags/newsletterConfirmed.js",
    "pimcore/object/tags/persona.js",
    "pimcore/object/tags/personamultiselect.js",
    "pimcore/object/preview.js",
    "pimcore/object/versions.js",
    "pimcore/object/variantsTab.js",
    "pimcore/object/importer.js",
    "pimcore/object/folder/search.js",
    "pimcore/object/edit.js",
    "pimcore/object/abstract.js",
    "pimcore/object/object.js",
    "pimcore/object/folder.js",
    "pimcore/object/variant.js",
    "pimcore/object/tree.js",
    "pimcore/object/customviews/settings.js",
    "pimcore/object/customviews/tree.js",

    //plugins
    "pimcore/plugin/broker.js",
    "pimcore/plugin/plugin.js",

    // reports
    "pimcore/report/panel.js",
    "pimcore/report/broker.js",
    "pimcore/report/abstract.js",
    "pimcore/report/settings.js",
    "pimcore/report/analytics/settings.js",
    "pimcore/report/analytics/elementoverview.js",
    "pimcore/report/analytics/elementexplorer.js",
    "pimcore/report/analytics/elementnavigation.js",
    "pimcore/report/webmastertools/settings.js",
    "pimcore/report/contentanalysis/settings.js",
    "pimcore/report/seo/detail.js",
    "pimcore/report/seo/socialoverview.js",
    "pimcore/report/custom/item.js",
    "pimcore/report/custom/panel.js",
    "pimcore/report/custom/settings.js",
    "pimcore/report/custom/report.js",
    "pimcore/report/custom/definitions/sql.js",
    "pimcore/report/custom/definitions/analytics.js",

    "pimcore/settings/tagmanagement/panel.js",
    "pimcore/settings/tagmanagement/item.js",

    "pimcore/report/qrcode/panel.js",
    "pimcore/report/qrcode/item.js",

    "pimcore/report/newsletter/panel.js",
    "pimcore/report/newsletter/item.js",

    // extension manager
    "pimcore/extensionmanager/settings.js",
    "pimcore/extensionmanager/xmlEditor.js",
    "pimcore/extensionmanager/admin.js",

    // layout
    "pimcore/layout/portal.js",
    "pimcore/layout/portlets/abstract.js",
    "pimcore/layout/portlets/modifiedDocuments.js",
    "pimcore/layout/portlets/modifiedObjects.js",
    "pimcore/layout/portlets/modifiedAssets.js",
    "pimcore/layout/portlets/modificationStatistic.js",
    "pimcore/layout/portlets/feed.js",
    "pimcore/layout/portlets/analytics.js",
    "pimcore/layout/portlets/customreports.js",

    "pimcore/layout/toolbar.js",
    "pimcore/layout/treepanelmanager.js",
    "pimcore/document/seemode.js",

    // keyvalue datatype
    "pimcore/object/keyvalue/panel.js",
    "pimcore/object/keyvalue/groupsPanel.js",
    "pimcore/object/keyvalue/propertiesPanel.js",
    "pimcore/object/keyvalue/selectionWindow.js",
    "pimcore/object/keyvalue/specialConfigWindow.js",
    "pimcore/object/keyvalue/columnConfigDialog.js",
    "pimcore/object/keyvalue/translatorConfigWindow.js"

);

// google maps API key
$googleMapsApiKey = $this->config->services->google->browserapikey;

?>

<!-- some javascript -->
<?php // pimcore constants ?>
<script type="text/javascript">
    pimcore.settings = {
        upload_max_filesize: <?php echo $this->upload_max_filesize; ?>,
        sessionId: "<?php echo htmlentities($_COOKIE["pimcore_admin_sid"], ENT_QUOTES, 'UTF-8') ?>",
        csrfToken: "<?= $this->csrfToken ?>",
        version: "<?php echo \Pimcore\Version::getVersion() ?>",
        build: "<?php echo \Pimcore\Version::$revision ?>",
        maintenance_active: <?php echo $this->maintenance_enabled; ?>,
        maintenance_mode: <?php echo \Pimcore\Tool\Admin::isInMaintenanceMode() ? "true" : "false"; ?>,
        mail: <?php echo $this->mail_settings_complete ?>,
        debug: <?php echo \Pimcore::inDebugMode() ? "true" : "false"; ?>,
        devmode: <?php echo PIMCORE_DEVMODE ? "true" : "false"; ?>,
        google_analytics_enabled: <?php echo \Zend_Json::encode((bool) \Pimcore\Google\Analytics::isConfigured()) ?>,
        google_webmastertools_enabled: <?php echo \Zend_Json::encode((bool) \Pimcore\Google\Webmastertools::isConfigured()) ?>,
        customviews: <?php echo \Zend_Json::encode($this->customview_config) ?>,
        language: '<?php echo $this->language; ?>',
        websiteLanguages: <?php echo \Zend_Json::encode(explode(",",$this->config->general->validLanguages)); ?>,
        google_translate_api_key: "<?php echo $this->config->services->translate->apikey; ?>",
        google_maps_api_key: "<?php echo $googleMapsApiKey ?>",
        showCloseConfirmation: true,
        debug_admin_translations: <?php echo \Zend_Json::encode((bool) $this->config->general->debug_admin_translations) ?>,
        document_generatepreviews: <?php echo \Zend_Json::encode((bool) $this->config->documents->generatepreview) ?>,
        htmltoimage: <?php echo \Zend_Json::encode(\Pimcore\Image\HtmlToImage::isSupported()) ?>,
        videoconverter: <?php echo \Zend_Json::encode(\Pimcore\Video::isAvailable()) ?>,
        asset_hide_edit: <?php echo $this->config->assets->hide_edit_image ? "true" : "false" ?>
    };
</script>


<?php // 3rd party libraries ?>
<script type="text/javascript">
    var gmapInitialize = function () {}; // dummy callback
    (function() {
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.src = 'https://maps.googleapis.com/maps/api/js?sensor=false&libraries=drawing&callback=gmapInitialize&key=<?php echo $googleMapsApiKey ?>';
        document.body.appendChild(script);
    })();
</script>

<script type="text/javascript" src="/admin/misc/json-translations-system/language/<?php echo $this->language ?>/?_dc=<?php echo \Pimcore\Version::$revision ?>"></script>
<script type="text/javascript" src="/admin/misc/json-translations-admin/language/<?php echo $this->language ?>/?_dc=<?php echo \Pimcore\Version::$revision ?>"></script>
<script type="text/javascript" src="/admin/user/get-current-user/?_dc=<?php echo \Pimcore\Version::$revision ?>"></script>
<script type="text/javascript" src="/admin/misc/available-languages?_dc=<?php echo \Pimcore\Version::$revision ?>"></script>


<!-- library scripts -->
<?php foreach ($scriptLibs as $scriptUrl) { ?>
    <script type="text/javascript" src="/pimcore/static/js/<?php echo $scriptUrl ?>?_dc=<?php echo \Pimcore\Version::$revision ?>"></script>
<?php } ?>



<!-- internal scripts -->
<?php if (PIMCORE_DEVMODE) { ?>
    <?php foreach ($scripts as $scriptUrl) { ?>
    <script type="text/javascript" src="/pimcore/static/js/<?php echo $scriptUrl ?>?_dc=<?php echo \Pimcore\Version::$revision ?>"></script>
<?php } ?>
<?php } else { ?>
<?php
$scriptContents = "";
foreach ($scripts as $scriptUrl) {
    if(is_file(PIMCORE_PATH."/static/js/".$scriptUrl)) {
        $scriptContents .= file_get_contents(PIMCORE_PATH."/static/js/".$scriptUrl) . "\n\n\n";
    }
}
?>
    <script type="text/javascript" src="<?php echo \Pimcore\Tool\Admin::getMinimizedScriptPath($scriptContents) ?>?_dc=<?php echo \Pimcore\Version::$revision ?>"></script>
<?php } ?>


<?php // load plugin scripts ?>
<?php

// only add the timestamp if the devmode is not activated, otherwise it is very hard to develop and debug plugins,
// because the filename changes on every reload and therefore breakpoints, ... are resetted on every reload
$pluginDcValue = time();
if(PIMCORE_DEVMODE) {
    $pluginDcValue = 1;
}

try {
    $pluginBroker = \Zend_Registry::get("Pimcore_API_Plugin_Broker");
    if ($pluginBroker instanceof \Pimcore\API\Plugin\Broker) {
        foreach ($pluginBroker->getSystemComponents() as $plugin) {
            if ($plugin->isInstalled()) {
                $jsPaths = $plugin->getJsPaths();
                if (!empty($jsPaths)) {
                    foreach ($jsPaths as $jsPath) {
                        $jsPath=trim($jsPath);
                        if (!empty($jsPath)) {
                            ?>
                            <script type="text/javascript" src="<?php echo $jsPath ?>?_dc=<?php echo $pluginDcValue; ?>"></script>
                        <?php

                        }
                    }
                }
                $cssPaths = $plugin->getCssPaths();
                if (!empty($cssPaths)) {
                    foreach ($cssPaths as $cssPath) {
                        $cssPath = trim($cssPath);
                        if (!empty($cssPath)) {
                            ?>
                            <link rel="stylesheet" type="text/css" href="<?php echo $cssPath ?>?_dc=<?php echo $pluginDcValue; ?>"/>
                        <?php

                        }
                    }
                }
            }
        }
    }
}
catch (\Exception $e) {}
?>

<?php // MUST BE THE LAST LINE ?>
<script type="text/javascript" src="/pimcore/static/js/pimcore/startup.js?_dc=<?php echo \Pimcore\Version::$revision ?>"></script>
</body>
</html>