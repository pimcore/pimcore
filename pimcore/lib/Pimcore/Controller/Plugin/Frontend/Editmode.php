<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Controller_Plugin_Frontend_Editmode extends Zend_Controller_Plugin_Abstract {
    protected $controller;

    public function __construct(Pimcore_Controller_Action_Frontend $controller) {
        $this->controller = $controller;
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request) {

        // add scripts to editmode
        
        $editmodeLibraries = array(
            "/pimcore/static/js/pimcore/namespace.js",
            
            "/pimcore/static/js/lib/prototype-light.js",
            "/pimcore/static/js/lib/jquery-1.7.1.min.js",
            "/pimcore/static/js/lib/ext/adapter/jquery/ext-jquery-adapter-debug.js",
            
            "/pimcore/static/js/lib/ext/ext-all-debug.js",
            "/pimcore/static/js/lib/ext-plugins/ux/Spinner.js",
            "/pimcore/static/js/lib/ext-plugins/ux/SpinnerField.js",
            "/pimcore/static/js/lib/ext-plugins/ux/MultiSelect.js",
            "/pimcore/static/js/lib/ext-plugins/ux/Portal.js",
            "/pimcore/static/js/lib/ext-plugins/ux/PortalColumn.js",
            "/pimcore/static/js/lib/ext-plugins/ux/Portlet.js",
            "/pimcore/static/js/lib/ext-plugins/GridRowOrder/roworder.js",
            "/pimcore/static/js/lib/ckeditor/ckeditor.js",
            "/pimcore/static/js/lib/ckeditor-plugins/pimcore-image.js",
            "/pimcore/static/js/lib/ckeditor-plugins/pimcore-link.js",
            "/pimcore/static/js/pimcore/libfixes.js"
        );
        
        $editmodeScripts = array(
            "/pimcore/static/js/pimcore/functions.js",
            "/pimcore/static/js/pimcore/document/edit/helper.js",
            "/pimcore/static/js/pimcore/document/edit/dnd.js",
            "/pimcore/static/js/pimcore/document/tag.js",
            "/pimcore/static/js/pimcore/document/tags/block.js",
            "/pimcore/static/js/pimcore/document/tags/date.js",
            "/pimcore/static/js/pimcore/document/tags/href.js",
            "/pimcore/static/js/pimcore/document/tags/multihref.js",
            "/pimcore/static/js/pimcore/document/tags/checkbox.js",
            "/pimcore/static/js/pimcore/document/tags/image.js",
            "/pimcore/static/js/pimcore/document/tags/input.js",
            "/pimcore/static/js/pimcore/document/tags/link.js",
            "/pimcore/static/js/pimcore/document/tags/select.js",
            "/pimcore/static/js/pimcore/document/tags/snippet.js",
            "/pimcore/static/js/pimcore/document/tags/textarea.js",
            "/pimcore/static/js/pimcore/document/tags/numeric.js",
            "/pimcore/static/js/pimcore/document/tags/wysiwyg.js",
            "/pimcore/static/js/pimcore/document/tags/renderlet.js",
            "/pimcore/static/js/pimcore/document/tags/table.js",
            "/pimcore/static/js/pimcore/document/tags/video.js",
            "/pimcore/static/js/pimcore/document/tags/multiselect.js",
            "/pimcore/static/js/pimcore/document/tags/areablock.js",
            "/pimcore/static/js/pimcore/document/tags/area.js",
            "/pimcore/static/js/pimcore/document/edit/helper.js"
        );


        $conf = Pimcore_Config::getSystemConfig();

        $themeUrl = "/pimcore/static/js/lib/ext/resources/css/xtheme-gray.css";

        $editmodeStylesheets = array(
            "/pimcore/static/js/lib/ext/resources/css/ext-all.css",
            $themeUrl,
            "/pimcore/static/css/icons.css",
            "/pimcore/static/css/editmode.css",
            "/pimcore/static/js/lib/ext-plugins/ux/css/Spinner.css",
            "/pimcore/static/js/lib/ext-plugins/ux/css/MultiSelect.css",
            "/pimcore/static/js/lib/ext-plugins/ux/css/Portal.css"
        );

        //add plugin editmode JS and CSS
        try {
            $pluginConfigs = Pimcore_ExtensionManager::getPluginConfigs();
            $jsPaths = array();
            $cssPaths = array();

            if (!empty($pluginConfigs)) {
                //registering plugins
                foreach ($pluginConfigs as $p) {

                    if (is_array($p['plugin']['pluginDocumentEditmodeJsPaths']['path'])) {
                        $jsPaths = $p['plugin']['pluginDocumentEditmodeJsPaths']['path'];
                    }
                    else if ($p['plugin']['pluginDocumentEditmodeJsPaths']['path'] != null) {
                        $jsPaths[0] = $p['plugin']['pluginDocumentEditmodeJsPaths']['path'];
                    }
                    //manipulate path for frontend
                    if (is_array($jsPaths) and count($jsPaths) > 0) {
                        for ($i = 0; $i < count($jsPaths); $i++) {
                            if (is_file(PIMCORE_PLUGINS_PATH . $jsPaths[$i])) {
                                $jsPaths[$i] = "/plugins" . $jsPaths[$i];
                            }
                        }
                    }


                    if (is_array($p['plugin']['pluginDocumentEditmodeCssPaths']['path'])) {
                        $cssPaths = $p['plugin']['pluginDocumentEditmodeCssPaths']['path'];
                    }
                    else if ($p['plugin']['pluginDocumentEditmodeCssPaths']['path'] != null) {
                        $cssPaths[0] = $p['plugin']['pluginDocumentEditmodeCssPaths']['path'];
                    }
                    //manipulate path for frontend
                    if (is_array($cssPaths) and count($cssPaths) > 0) {
                        for ($i = 0; $i < count($cssPaths); $i++) {
                            if (is_file(PIMCORE_PLUGINS_PATH . $cssPaths[$i])) {
                                $cssPaths[$i] = "/plugins" . $cssPaths[$i];
                            }
                        }
                    }

                }
            }

            $editmodeScripts=array_merge($editmodeScripts,$jsPaths);
            $editmodeStylesheets=array_merge($editmodeStylesheets,$cssPaths);
            
        }
        catch (Exception $e) {
            Logger::alert("there is a problem with the plugin configuration");
            Logger::alert($e);
        }

        $editmodeHeadHtml = "\n\n\n<!-- pimcore editmode -->\n";

        // include stylesheets
        foreach ($editmodeStylesheets as $sheet) {
            $editmodeHeadHtml .= '<link rel="stylesheet" type="text/css" href="' . $sheet . '?_dc=' . Pimcore_Version::$revision . '" />';
            $editmodeHeadHtml .= "\n";
        }
        
        // include script libraries
        foreach ($editmodeLibraries as $script) {
            $editmodeHeadHtml .= '<script type="text/javascript" src="' . $script . '?_dc=' . Pimcore_Version::$revision . '"></script>';
            $editmodeHeadHtml .= "\n";
        }
        
        // combine the pimcore scripts in non-devmode
        if($conf->general->devmode) {
            foreach ($editmodeScripts as $script) {
                $editmodeHeadHtml .= '<script type="text/javascript" src="' . $script . '?_dc=' . Pimcore_Version::$revision . '"></script>';
                $editmodeHeadHtml .= "\n";
            }
        }
        else {
            $scriptContents = "";
            foreach ($editmodeScripts as $scriptUrl) {
                $scriptContents .= file_get_contents(PIMCORE_DOCUMENT_ROOT.$scriptUrl) . "\n\n\n";
            }
            $editmodeHeadHtml .= '<script type="text/javascript" src="' . Pimcore_Tool_Admin::getMinimizedScriptPath($scriptContents) . '?_dc=' . Pimcore_Version::$revision . '"></script>'."\n";
        }

        $user = Pimcore_Tool_Authentication::authenticateSession();
        $lang = $user->getLanguage();

        $editmodeHeadHtml .= '<script type="text/javascript" src="/admin/misc/json-translations-system/language/'.$lang.'/?_dc=' . Pimcore_Version::$revision . '"></script>'."\n";
        $editmodeHeadHtml .= '<script type="text/javascript" src="/admin/misc/json-translations-admin/language/'.$lang.'/?_dc=' . Pimcore_Version::$revision . '"></script>'."\n";

        
        $editmodeHeadHtml .= "\n\n";
        
        // set var for editable configurations which is filled by Document_Tag::admin()
        $editmodeHeadHtml .= '<script type="text/javascript">
            var editableConfigurations = new Array();
            var pimcore_document_id = ' . $request->getParam("document")->getId() . ';
        </script>';
        
        $editmodeHeadHtml .= "\n\n<!-- /pimcore editmode -->\n\n\n";

        // add html headers for snippets in editmode, so there is no problem with javascript
        $body = $this->getResponse()->getBody();
        if ($this->controller->editmode && strpos($body, "</body>") === false && !$request->getParam("blockAutoHtml")) {
            $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
			<head></head><body>' . $body . "</body></html>";

            $this->getResponse()->setBody($body);
        }

        // add scripts in html header for pages in editmode
        if ($this->controller->editmode && Document_Service::isValidType($this->controller->document->getType()) ) { //ckogler
            include_once("simple_html_dom.php");
            $body = $this->getResponse()->getBody();

            $html = str_get_html($body);
            if($html) {
                if($head = $html->find("head", 0)) {

                    $head->innertext = $head->innertext . "\n\n" . $editmodeHeadHtml;

                    $bodyElement = $html->find("body", 0);
                    $bodyElement->onunload = "pimcoreOnUnload();";
                    $bodyElement->innertext = $bodyElement->innertext . "\n\n" . '<script type="text/javascript" src="/pimcore/static/js/pimcore/document/edit/startup.js?_dc=' . Pimcore_Version::$revision . '"></script>' . "\n\n";

                    $body = $html->save();

                    $this->getResponse()->setBody($body);
                }
            }
        }
    }
}
