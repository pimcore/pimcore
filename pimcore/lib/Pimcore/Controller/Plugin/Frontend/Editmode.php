<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Plugin\Frontend;

use Pimcore\Version;
use Pimcore\ExtensionManager;
use Pimcore\Config;
use Pimcore\Model\Document;
use Pimcore\Logger;

class Editmode extends \Zend_Controller_Plugin_Abstract
{

    /**
     * @var \Pimcore\Controller\Action\Frontend
     */
    protected $controller;

    /**
     * @param \Pimcore\Controller\Action\Frontend $controller
     */
    public function __construct(\Pimcore\Controller\Action\Frontend $controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function postDispatch(\Zend_Controller_Request_Abstract $request)
    {
        $conf = Config::getSystemConfig();

        // add scripts to editmode

        $debugSuffix = "";
        if (PIMCORE_DEVMODE) {
            $debugSuffix = "-debug";
        }

        $editmodeLibraries = [
            "/pimcore/static6/js/pimcore/namespace.js",
            "/pimcore/static6/js/lib/prototype-light.js",
            "/pimcore/static6/js/lib/jquery.min.js",
            "/pimcore/static6/js/lib/ext/ext-all" . $debugSuffix . ".js",
            "/pimcore/static6/js/lib/ckeditor/ckeditor.js"
        ];

        $editmodeScripts = [
            "/pimcore/static6/js/pimcore/functions.js",
            "/pimcore/static6/js/pimcore/element/tag/imagehotspotmarkereditor.js",
            "/pimcore/static6/js/pimcore/element/tag/imagecropper.js",
            "/pimcore/static6/js/pimcore/document/edit/helper.js",
            "/pimcore/static6/js/pimcore/elementservice.js",
            "/pimcore/static6/js/pimcore/document/edit/dnd.js",
            "/pimcore/static6/js/pimcore/document/tag.js",
            "/pimcore/static6/js/pimcore/document/tags/block.js",
            "/pimcore/static6/js/pimcore/document/tags/date.js",
            "/pimcore/static6/js/pimcore/document/tags/href.js",
            "/pimcore/static6/js/pimcore/document/tags/multihref.js",
            "/pimcore/static6/js/pimcore/document/tags/checkbox.js",
            "/pimcore/static6/js/pimcore/document/tags/image.js",
            "/pimcore/static6/js/pimcore/document/tags/input.js",
            "/pimcore/static6/js/pimcore/document/tags/link.js",
            "/pimcore/static6/js/pimcore/document/tags/select.js",
            "/pimcore/static6/js/pimcore/document/tags/snippet.js",
            "/pimcore/static6/js/pimcore/document/tags/textarea.js",
            "/pimcore/static6/js/pimcore/document/tags/numeric.js",
            "/pimcore/static6/js/pimcore/document/tags/wysiwyg.js",
            "/pimcore/static6/js/pimcore/document/tags/renderlet.js",
            "/pimcore/static6/js/pimcore/document/tags/table.js",
            "/pimcore/static6/js/pimcore/document/tags/video.js",
            "/pimcore/static6/js/pimcore/document/tags/multiselect.js",
            "/pimcore/static6/js/pimcore/document/tags/areablock.js",
            "/pimcore/static6/js/pimcore/document/tags/area.js",
            "/pimcore/static6/js/pimcore/document/tags/pdf.js",
            "/pimcore/static6/js/pimcore/document/tags/embed.js",
            "/pimcore/static6/js/pimcore/document/edit/helper.js"
        ];

        $editmodeStylesheets = [
            "/pimcore/static6/css/icons.css",
            "/pimcore/static6/css/editmode.css?_dc=" . time()
        ];


        //add plugin editmode JS and CSS
        try {
            $pluginConfigs = ExtensionManager::getPluginConfigs();
            $jsPaths = [];
            $cssPaths = [];

            if (!empty($pluginConfigs)) {
                //registering plugins
                foreach ($pluginConfigs as $p) {
                    $pluginJsPaths = [];
                    $pluginVersions = ["-extjs6", ""];

                    foreach ($pluginVersions as $pluginVersion) {
                        if (array_key_exists("pluginDocumentEditmodeJsPaths".$pluginVersion, $p['plugin'])
                            && is_array($p['plugin']['pluginDocumentEditmodeJsPaths'.$pluginVersion])
                            && isset($p['plugin']['pluginDocumentEditmodeJsPaths'.$pluginVersion]['path'])) {
                            if (is_array($p['plugin']['pluginDocumentEditmodeJsPaths'.$pluginVersion]['path'])) {
                                $pluginJsPaths = $p['plugin']['pluginDocumentEditmodeJsPaths'.$pluginVersion]['path'];
                                break;
                            } elseif ($p['plugin']['pluginDocumentEditmodeJsPaths'.$pluginVersion]['path'] != null) {
                                $pluginJsPaths[] = $p['plugin']['pluginDocumentEditmodeJsPaths'.$pluginVersion]['path'];
                                break;
                            }
                        }
                    }

                    //manipulate path for frontend
                    if (is_array($pluginJsPaths) and count($pluginJsPaths) > 0) {
                        for ($i = 0; $i < count($pluginJsPaths); $i++) {
                            if (is_file(PIMCORE_PLUGINS_PATH . $pluginJsPaths[$i])) {
                                $jsPaths[] = "/plugins" . $pluginJsPaths[$i];
                            }
                        }
                    }


                    $pluginCssPaths = [];
                    foreach ($pluginVersions as $pluginVersion) {
                        if (array_key_exists("pluginDocumentEditmodeCssPaths".$pluginVersion, $p['plugin'])
                            && is_array($p['plugin']['pluginDocumentEditmodeCssPaths'.$pluginVersion])
                            && isset($p['plugin']['pluginDocumentEditmodeCssPaths'.$pluginVersion]['path'])
                        ) {
                            if (is_array($p['plugin']['pluginDocumentEditmodeCssPaths'.$pluginVersion]['path'])) {
                                $pluginCssPaths = $p['plugin']['pluginDocumentEditmodeCssPaths'.$pluginVersion]['path'];
                                break;
                            } elseif ($p['plugin']['pluginDocumentEditmodeCssPaths'.$pluginVersion]['path'] != null) {
                                $pluginCssPaths[] = $p['plugin']['pluginDocumentEditmodeCssPaths'.$pluginVersion]['path'];
                                break;
                            }
                        }
                    }

                    //manipulate path for frontend
                    if (is_array($pluginCssPaths) and count($pluginCssPaths) > 0) {
                        for ($i = 0; $i < count($pluginCssPaths); $i++) {
                            if (is_file(PIMCORE_PLUGINS_PATH . $pluginCssPaths[$i])) {
                                $cssPaths[] = "/plugins" . $pluginCssPaths[$i];
                            }
                        }
                    }
                }
            }

            $editmodeScripts=array_merge($editmodeScripts, $jsPaths);
            $editmodeStylesheets=array_merge($editmodeStylesheets, $cssPaths);
        } catch (\Exception $e) {
            Logger::alert("there is a problem with the plugin configuration");
            Logger::alert($e);
        }

        $editmodeHeadHtml = "\n\n\n<!-- pimcore editmode -->\n";
        $editmodeHeadHtml .= '<meta name="google" value="notranslate">';
        $editmodeHeadHtml .= "\n\n";

        // include stylesheets
        foreach ($editmodeStylesheets as $sheet) {
            $editmodeHeadHtml .= '<link rel="stylesheet" type="text/css" href="' . $sheet . '?_dc=' . Version::$revision . '" />';
            $editmodeHeadHtml .= "\n";
        }

        $editmodeHeadHtml .= "\n\n";

        $editmodeHeadHtml .= '<script type="text/javascript">var jQueryPreviouslyLoaded = (typeof jQuery == "undefined") ? false : true;</script>' . "\n";

        // include script libraries
        foreach ($editmodeLibraries as $script) {
            $editmodeHeadHtml .= '<script type="text/javascript" src="' . $script . '?_dc=' . Version::$revision . '"></script>';
            $editmodeHeadHtml .= "\n";
        }

        // combine the pimcore scripts in non-devmode
        if ($conf->general->devmode) {
            foreach ($editmodeScripts as $script) {
                $editmodeHeadHtml .= '<script type="text/javascript" src="' . $script . '?_dc=' . Version::$revision . '"></script>';
                $editmodeHeadHtml .= "\n";
            }
        } else {
            $scriptContents = "";
            foreach ($editmodeScripts as $scriptUrl) {
                $scriptContents .= file_get_contents(PIMCORE_DOCUMENT_ROOT.$scriptUrl) . "\n\n\n";
            }
            $editmodeHeadHtml .= '<script type="text/javascript" src="' . \Pimcore\Tool\Admin::getMinimizedScriptPath($scriptContents) . '"></script>'."\n";
        }

        $user = \Pimcore\Tool\Authentication::authenticateSession();
        $lang = $user->getLanguage();

        $editmodeHeadHtml .= '<script type="text/javascript" src="/admin/misc/json-translations-system/language/'.$lang.'/?_dc=' . Version::$revision . '"></script>'."\n";
        $editmodeHeadHtml .= '<script type="text/javascript" src="/admin/misc/json-translations-admin/language/'.$lang.'/?_dc=' . Version::$revision . '"></script>'."\n";


        $editmodeHeadHtml .= "\n\n";

        // set var for editable configurations which is filled by Document\Tag::admin()
        $editmodeHeadHtml .= '<script type="text/javascript">
            var editableConfigurations = new Array();
            var pimcore_document_id = ' . $request->getParam("document")->getId() . ';

            if(jQueryPreviouslyLoaded) {
                jQuery.noConflict( true );
            }
        </script>';

        $editmodeHeadHtml .= "\n\n<!-- /pimcore editmode -->\n\n\n";


        // add scripts in html header for pages in editmode
        if ($this->controller->editmode && Document\Service::isValidType($this->controller->document->getType())) { //ckogler
            include_once("simple_html_dom.php");
            $html = $this->getResponse()->getBody();

            if ($html) {
                $htmlElement = preg_match("/<html[^a-zA-Z]?(\s[^>]+)?>/", $html);
                $headElement = preg_match("/<head[^a-zA-Z]?(\s[^>]+)?>/", $html);
                $bodyElement = preg_match("/<body[^a-zA-Z]?(\s[^>]+)?>/", $html);

                $skipCheck = false;

                // if there's no head and no body, create a wrapper including these elements
                // add html headers for snippets in editmode, so there is no problem with javascript
                if (!$headElement && !$bodyElement && !$htmlElement) {
                    $html = "<!DOCTYPE html>\n<html>\n<head></head><body>" . $html . "</body></html>";
                    $skipCheck = true;
                }

                if ($skipCheck || ($headElement && $bodyElement && $htmlElement)) {
                    $html = preg_replace("@</head>@i", $editmodeHeadHtml . "\n\n</head>", $html, 1);

                    $startupJavascript = "/pimcore/static6/js/pimcore/document/edit/startup.js";

                    $editmodeBodyHtml = "\n\n" . '<script type="text/javascript" src="' . $startupJavascript . '?_dc=' . Version::$revision . '"></script>' . "\n\n";
                    $html = preg_replace("@</body>@i", $editmodeBodyHtml . "\n\n</body>", $html, 1);

                    $this->getResponse()->setBody($html);
                } else {
                    $this->getResponse()->setBody('<div style="font-size:30px; font-family: Arial; font-weight:bold; color:red; text-align: center; margin: 40px 0">You have to define a &lt;html&gt;, &lt;head&gt;, &lt;body&gt;<br />HTML-tag in your view/layout markup!</div>');
                }
            }
        }
    }

    /**
     *
     */
    public function dispatchLoopShutdown()
    {
        $this->getResponse()->setHeader("X-Frame-Options", "SAMEORIGIN", true);
    }
}
