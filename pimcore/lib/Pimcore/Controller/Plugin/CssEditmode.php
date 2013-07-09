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

class Pimcore_Controller_Plugin_CssEditmode extends Zend_Controller_Plugin_Abstract {

    public function dispatchLoopShutdown() {

        return;
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        if ($this->getRequest()->getParam("pimcore_editmode")) {
            include_once("simple_html_dom.php");

            $body = $this->getResponse()->getBody();
            
            $html = str_get_html($body);
            if($html) {
                $styles = $html->find("link[rel=stylesheet]");

                foreach ($styles as $style) {

                    $source = $style->href;
                    $path = "";
                    if (is_file(PIMCORE_ASSET_DIRECTORY . $source)) {
                        $path = PIMCORE_ASSET_DIRECTORY . $source;
                    }
                    else if (is_file(PIMCORE_DOCUMENT_ROOT . $source)) {
                        $path = PIMCORE_DOCUMENT_ROOT . $source;
                    }

                    if (!empty($path) && is_file("file://".$path)) {

                        $stylesheetPath = PIMCORE_TEMPORARY_DIRECTORY."/editmode-css_".md5($path) . "-" . time() .".css";

                        if(!is_file($stylesheetPath)) {
                            $content = file_get_contents($path);

                            $content = "/* ORIGINAL SOURCE: " . $source . " */" . $content;

                            $content = preg_replace_callback("/([a-zA-Z0-9]+)[\s]+{[^}]+}/", function ($matches) {
                                $c = trim($matches[0]);
                                $c = substr_replace($c, $matches[1] . ':not([class^="x-"]) ', 0, strpos($c, "{"));
                                return $c;
                            }, $content);

                            // put the new css into a file and rewrite the path of the link element
                            file_put_contents($stylesheetPath, $content);
                        }

                        $style->href = str_replace(PIMCORE_DOCUMENT_ROOT, "", $stylesheetPath);
                    }
                }

                $body = $html->save();

                $html->clear();
                unset($html);

                $this->getResponse()->setBody($body);
            }
        }
    }
}
