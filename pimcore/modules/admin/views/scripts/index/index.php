<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="apple-mobile-web-app-capable" content="yes" />

    <title><?php echo htmlentities($this->getRequest()->getHttpHost(), ENT_QUOTES, 'UTF-8') ?> :: pimcore</title>

    <!-- load in head because of the progress bar at loading -->
    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/admin.css?_dc=<?php echo Pimcore_Version::$revision ?>" />
</head>

<body>
    
    <div id="pimcore_logo" style="display: none;">
        <img src="/pimcore/static/img/logo.png"/>
    </div>
    
    <div id="pimcore_loading">
        <img class="logo" src="/pimcore/static/img/loading-logo.png?_dc=<?php echo Pimcore_Version::$revision ?>" />
        <img class="loading" src="/pimcore/static/img/loading.gif?_dc=<?php echo Pimcore_Version::$revision ?>" />
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
            "/pimcore/static/js/lib/ext-plugins/SwfUploadPanel/SwfUploadPanel.css",
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
            "/pimcore/static/css/ext-admin-overwrite.css"
        );
    ?>

    <!-- stylesheets -->
    <?php foreach ($styles as $style) { ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $style ?>?_dc=<?php echo Pimcore_Version::$revision ?>" />
    <?php } ?>





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
            "lib/jquery-1.7.1.min.js",
            "lib/jquery.color.js",
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
            "lib/ckeditor-plugins/pimcore-image.js",
            "lib/ckeditor-plugins/pimcore-link.js",

            // locale
            "lib/ext/locale/ext-lang-" . $this->language . ".js",
        );

        // browser specific lib includes
        $browser = new Pimcore_Browser();
        $browserVersion = (int) $browser->getVersion();
        $platform = $browser->getPlatform();


        // ace editor (code editor in server file explorer) is only for => IE9, FF, Chrome
        if ( ($browser->getBrowser() == Pimcore_Browser::BROWSER_IE && $browserVersion >= 9) || $browser->getBrowser() != Pimcore_Browser::BROWSER_IE) {
            $scriptLibs[] = "lib/ace/ace-noconflict.js";
        }


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
            "pimcore/settings/user/role/settings.js",
            "pimcore/settings/profile/panel.js",
            "pimcore/settings/thumbnail/item.js",
            "pimcore/settings/thumbnail/panel.js",
            "pimcore/settings/videothumbnail/item.js",
            "pimcore/settings/videothumbnail/panel.js",
            "pimcore/settings/translations.js",
            "pimcore/settings/translation/website.js",
            "pimcore/settings/translation/admin.js",
            "pimcore/settings/properties/predefined.js",
            "pimcore/settings/docTypes.js",
            "pimcore/settings/system.js",
            "pimcore/settings/website.js",
            "pimcore/settings/staticroutes.js",
            "pimcore/settings/update.js",
            "pimcore/settings/languages.js",
            "pimcore/settings/redirects.js",
            "pimcore/settings/glossary.js",
            "pimcore/settings/systemlog.js",
            "pimcore/settings/backup.js",
            "pimcore/settings/recyclebin.js",
            "pimcore/settings/fileexplorer/file.js",
            "pimcore/settings/fileexplorer/explorer.js",
            "pimcore/settings/maintenance.js",
            "pimcore/settings/liveconnect.js",
            "pimcore/settings/robotstxt.js",
            "pimcore/settings/httpErrorLog.js",
            "pimcore/settings/targeting/panel.js",
            "pimcore/settings/targeting/item.js",

            // element
            "pimcore/element/abstract.js",
            "pimcore/element/selector/selector.js",
            "pimcore/element/selector/abstract.js",
            "pimcore/element/selector/document.js",
            "pimcore/element/selector/asset.js",
            "pimcore/element/properties.js",
            "pimcore/element/scheduler.js",
            "pimcore/element/dependencies.js",
            "pimcore/element/notes.js",
            "pimcore/object/helpers/grid.js",
            "pimcore/object/helpers/gridConfigDialog.js",
            "pimcore/object/helpers/classTree.js",
            "pimcore/object/helpers/gridTabAbstract.js",
            "pimcore/element/selector/object.js",

            // documents
            "pimcore/document/properties.js",
            "pimcore/document/document.js",
            "pimcore/document/page_snippet.js",
            "pimcore/document/edit.js",
            "pimcore/document/versions.js",
            "pimcore/document/pages/settings.js",
            "pimcore/document/pages/preview.js",
            "pimcore/document/pages/targeting.js",
            "pimcore/document/pages/target/item.js",
            "pimcore/document/snippets/settings.js",
            "pimcore/document/emails/settings.js",
            "pimcore/document/emails/logs.js",
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
            "pimcore/asset/versions.js",
            "pimcore/asset/tree.js",
        
            // object
            "pimcore/object/helpers/edit.js",
            "pimcore/object/classes/class.js",
            "pimcore/object/class.js",
            "pimcore/object/classes/data/data.js",
            "pimcore/object/classes/data/date.js",
            "pimcore/object/classes/data/datetime.js",
            "pimcore/object/classes/data/time.js",
            "pimcore/object/classes/data/href.js",
            "pimcore/object/classes/data/image.js",
            "pimcore/object/classes/data/hotspotimage.js",
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
            "pimcore/object/classes/data/geopoint.js",
            "pimcore/object/classes/data/language.js",
            "pimcore/object/classes/data/password.js",
            "pimcore/object/classes/data/multiselect.js",
            "pimcore/object/classes/data/link.js",
            "pimcore/object/classes/data/geobounds.js",
            "pimcore/object/classes/data/geopolygon.js",
            "pimcore/object/classes/data/fieldcollections.js",
            "pimcore/object/classes/data/objectbricks.js",
            "pimcore/object/classes/data/localizedfields.js",
            "pimcore/object/classes/data/countrymultiselect.js",
            "pimcore/object/classes/data/languagemultiselect.js",
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
            "pimcore/object/tags/geopoint.js",
            "pimcore/object/tags/language.js",
            "pimcore/object/tags/password.js",
            "pimcore/object/tags/multiselect.js",
            "pimcore/object/tags/link.js",
            "pimcore/object/tags/geobounds.js",
            "pimcore/object/tags/geopolygon.js",
            "pimcore/object/tags/fieldcollections.js",
            "pimcore/object/tags/localizedfields.js",
            "pimcore/object/tags/countrymultiselect.js",
            "pimcore/object/tags/languagemultiselect.js",
            "pimcore/object/tags/objectbricks.js",
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

            "pimcore/settings/tagmanagement/panel.js",
            "pimcore/settings/tagmanagement/item.js",

            "pimcore/report/qrcode/panel.js",
            "pimcore/report/qrcode/item.js",

            // extension manager
            "pimcore/extensionmanager/settings.js",
            "pimcore/extensionmanager/xmlEditor.js",
            "pimcore/extensionmanager/admin.js",
            "pimcore/extensionmanager/download.js",
            "pimcore/extensionmanager/share.js",

            // layout
            "pimcore/layout/portal.js",
            "pimcore/layout/portlets/abstract.js",
            "pimcore/layout/portlets/modifiedDocuments.js",
            "pimcore/layout/portlets/modifiedObjects.js",
            "pimcore/layout/portlets/modifiedAssets.js",
            "pimcore/layout/portlets/modificationStatistic.js",
            "pimcore/layout/portlets/feed.js",
            "pimcore/layout/portlets/analytics.js",
            
            "pimcore/layout/toolbar.js",
            "pimcore/layout/treepanelmanager.js",
            "pimcore/document/seemode.js"
        );

        // they're here because they are using some pimcore core functionality like t() for i18n , ...
        $modifiedPlugins = array(
            "lib/ext-plugins/SwfUploadPanel/swfupload.js",
            "lib/ext-plugins/SwfUploadPanel/SwfUploadPanel.js"
        );

        // google maps API key
        $googleMapsApiKey = $this->config->services->google->simpleapikey;
        if($this->config->services->google->browserapikey) {
            $googleMapsApiKey = $this->config->services->google->browserapikey;
        }

    ?>
    
    <!-- some javascript -->
    <?php // pimcore constants ?>
    <script type="text/javascript">
        pimcore.settings = {
            upload_max_filesize: <?php echo $this->upload_max_filesize; ?>,
            sessionId: "<?php echo htmlentities($_COOKIE["pimcore_admin_sid"], ENT_QUOTES, 'UTF-8') ?>",
            version: "<?php echo Pimcore_Version::getVersion() ?>",
            build: "<?php echo Pimcore_Version::$revision ?>",
            maintenance_active: <?php echo $this->maintenance_enabled; ?>,
            maintenance_mode: <?php echo Pimcore_Tool_Admin::isInMaintenanceMode() ? "true" : "false"; ?>,
            mail: <?php echo $this->mail_settings_incomplete ?>,
            debug: <?php echo Pimcore::inDebugMode() ? "true" : "false"; ?>,
            devmode: <?php echo PIMCORE_DEVMODE ? "true" : "false"; ?>,
            google_analytics_enabled: <?php echo Zend_Json::encode((bool) Pimcore_Google_Analytics::isConfigured()) ?>,
            google_analytics_advanced: <?php echo Zend_Json::encode((bool) Pimcore_Google_Analytics::getSiteConfig()->advanced); ?>,
            google_webmastertools_enabled: <?php echo Zend_Json::encode((bool) Pimcore_Google_Webmastertools::isConfigured()) ?>,
            customviews: <?php echo Zend_Json::encode($this->customview_config) ?>,
            language: '<?php echo $this->language; ?>',
            websiteLanguages: <?php echo Zend_Json::encode(explode(",",$this->config->general->validLanguages)); ?>,
            google_translate_api_key: "<?php echo $this->config->services->translate->apikey; ?>",
            google_maps_api_key: "<?php echo $googleMapsApiKey ?>",
            liveconnectToken: "<?php echo $this->liveconnectToken; ?>",
            showCloseConfirmation: true,
            debug_admin_translations: <?php echo Zend_Json::encode((bool) $this->config->general->debug_admin_translations) ?>,
            targeting_enabled: <?php echo Zend_Json::encode((bool) $this->config->general->targeting) ?>
        };
    </script>
    
    
    <?php // 3rd party libraries ?>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false&key=<?php echo $googleMapsApiKey ?>"></script>

    <script type="text/javascript" src="/admin/misc/json-translations-system/language/<?php echo $this->language ?>/?_dc=<?php echo Pimcore_Version::$revision ?>"></script>
    <script type="text/javascript" src="/admin/misc/json-translations-admin/language/<?php echo $this->language ?>/?_dc=<?php echo Pimcore_Version::$revision ?>"></script>
    <script type="text/javascript" src="/admin/user/get-current-user/?_dc=<?php echo Pimcore_Version::$revision ?>"></script>
    <script type="text/javascript" src="/admin/misc/available-languages?_dc=<?php echo Pimcore_Version::$revision ?>"></script>
    
    
    <!-- library scripts -->
    <?php foreach ($scriptLibs as $scriptUrl) { ?>
        <script type="text/javascript" src="/pimcore/static/js/<?php echo $scriptUrl ?>?_dc=<?php echo Pimcore_Version::$revision ?>"></script>
    <?php } ?>
    
    
    
    <!-- internal scripts -->
    <?php if (PIMCORE_DEVMODE) { ?>
        <?php foreach ($scripts as $scriptUrl) { ?>
            <script type="text/javascript" src="/pimcore/static/js/<?php echo $scriptUrl ?>?_dc=<?php echo Pimcore_Version::$revision ?>"></script>
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
        <script type="text/javascript" src="<?php echo Pimcore_Tool_Admin::getMinimizedScriptPath($scriptContents) ?>?_dc=<?php echo Pimcore_Version::$revision ?>"></script>
    <?php } ?>

    <!-- modified plugins -->
    <?php foreach ($modifiedPlugins as $scriptUrl) { ?>
        <script type="text/javascript" src="/pimcore/static/js/<?php echo $scriptUrl ?>?_dc=<?php echo Pimcore_Version::$revision ?>"></script>
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
            $pluginBroker = Zend_Registry::get("Pimcore_API_Plugin_Broker");
            if ($pluginBroker instanceof Pimcore_API_Plugin_Broker) {
                foreach ($pluginBroker->getPlugins() as $plugin) {
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
        catch (Exception $e) {}
    ?>

    <?php // MUST BE THE LAST LINE ?>
    <script type="text/javascript" src="/pimcore/static/js/pimcore/startup.js?_dc=<?php echo Pimcore_Version::$revision ?>"></script>

</body>
</html>
