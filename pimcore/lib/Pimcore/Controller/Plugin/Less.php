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

require_once 'Zend/Controller/Plugin/Abstract.php';
require_once 'simple_html_dom.php';
require_once 'lessc.inc.php';

class Pimcore_Controller_Plugin_Less extends Zend_Controller_Plugin_Abstract {

    protected $enabled = true;

    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        $conf = Zend_Registry::get("pimcore_config_system");
        if (!$conf->outputfilters) {
            return $this->disable();
        }

        if (!$conf->outputfilters->less) {
            return $this->disable();
        }

    }

    public function disable() {
        $this->enabled = false;
        return true;
    }

    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        if ($this->enabled) {

            include_once("simple_html_dom.php");

            if($this->getRequest()->getParam("pimcore_editmode")) {
                $this->editmode();
            } else {
                $this->frontend();
            }
        }
    }

    public function frontend () {
   
        $body = $this->getResponse()->getBody();

        $html = str_get_html($body);
        $styles = $html->find("link[rel=stylesheet/less]");

        $stylesheetContents = array();

        foreach ($styles as $style) {

            $media = $style->media;
            if(!$media) {
                $media = "all";
            }

            $source = $style->href;
            $path = "";
            if (is_file(PIMCORE_ASSET_DIRECTORY . $source)) {
                $path = PIMCORE_ASSET_DIRECTORY . $source;
            }
            else if (is_file(PIMCORE_DOCUMENT_ROOT . $source)) {
                $path = PIMCORE_DOCUMENT_ROOT . $source;
            }

            if (is_file("file://".$path)) {
                $stylesheetContents[$media] .= file_get_contents($path) . "\n";
                $style->outertext = "";
            }
        }

        if(count($stylesheetContents) > 0) {
            $head = $html->find("head",0);
            foreach ($stylesheetContents as $media => $content) {
                $stylesheetPath = PIMCORE_TEMPORARY_DIRECTORY."/less_".md5($content).".css";

                if(!is_file($stylesheetPath)) {
                    // put compiled contents into one single file
                    $less = new lessc();
                    $compiledContent = $less->parse($content);
                    file_put_contents($stylesheetPath, $compiledContent);
                }

                $head->innertext = $head->innertext . "\n" . '<link rel="stylesheet" media="' . $media . '" type="text/css" href="' . str_replace(PIMCORE_DOCUMENT_ROOT,"",$stylesheetPath) . '" />'."\n";
            }
        }

        $body = $html->save();
        $this->getResponse()->setBody($body);
    }

    public function editmode () {
        $body = $this->getResponse()->getBody();

        $html = str_get_html($body);
        $head = $html->find("head",0);
        $head->innertext = $head->innertext . "\n" . '<script type="text/javascript" src="/pimcore/static/js/lib/less.js" />'."\n";

        $body = $html->save();
        $this->getResponse()->setBody($body);
    }
}

