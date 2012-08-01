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

class Pimcore_Controller_Plugin_JavascriptMinify extends Zend_Controller_Plugin_Abstract {

    protected $enabled = true;
    protected $conf;

    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        $conf = Pimcore_Config::getSystemConfig();
        if (!$conf->outputfilters) {
            return $this->disable();
        }

        if (!$conf->outputfilters->javascriptminify) {
            return $this->disable();
        }
        
        $this->conf = $conf;
    }

    public function disable() {
        $this->enabled = false;
        return true;
    }
    
    public function minify ($js) {

        try {
            if($this->conf->outputfilters->javascriptminifyalgorithm == "jsminplus") {
            $js = JSMinPlus::minify($js);
            }
            else if ($this->conf->outputfilters->javascriptminifyalgorithm == "yuicompressor") {
                Minify_YUICompressor::$tempDir = PIMCORE_TEMPORARY_DIRECTORY;
                Minify_YUICompressor::$jarFile = PIMCORE_PATH . "/lib/Minify/yuicompressor-2.4.2.jar";
                $js = Minify_YUICompressor::minifyJs($js);
            }
            else {
                $js = JSMin::minify($js);
            }
        }
        catch (Exception $e) {
            Logger::error("Unable to minify javascript");
            Logger::error($e);
        }
        
        return $js;
    }

    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        if ($this->enabled) {
            include_once("simple_html_dom.php");
            
            $body = $this->getResponse()->getBody();
            
            $html = str_get_html($body);

            if($html) {
                $scripts = $html->find("script[src]");
                $scriptContent = "";

                foreach ($scripts as $script) {

                    $source = $script->src;
                    $path = "";

                    if(!preg_match("@http(s)?://@i", $source)) {
                        if (@is_file("file://".PIMCORE_ASSET_DIRECTORY . $source)) {
                            $path = "file://".PIMCORE_ASSET_DIRECTORY . $source;
                        }
                        else if (@is_file("file://".PIMCORE_DOCUMENT_ROOT . $source)) {
                            $path = "file://".PIMCORE_DOCUMENT_ROOT . $source;
                        }
                    }


                    if ($path && @is_file($path)) {
                        $scriptContent .= file_get_contents($path)."\n\n";

                        if($script->next_sibling()->tag != "script" || !$script->next_sibling()->src) {
                            $scriptPath = $this->writeJsTempFile($scriptContent);
                            $scriptContent = "";

                            $script->outertext = '<script type="text/javascript" src="' .  str_replace(PIMCORE_DOCUMENT_ROOT,"",$scriptPath) . '"></script>'."\n";
                        }
                        else {
                            $script->outertext = "";
                        }
                    }
                    else if ($script->prev_sibling()->tag == "script"){

                        if (strlen($scriptContent) > 0) {
                            $scriptPath = $this->writeJsTempFile($scriptContent);
                            $scriptContent = "";

                            $script->outertext = '<script type="text/javascript" src="' .  str_replace(PIMCORE_DOCUMENT_ROOT,"",$scriptPath) . '"></script>'."\n" . $script->outertext;
                        }
                    }
                }

                $body = $html->save();
                $this->getResponse()->setBody($body);
            }
        }
    }
    
    protected function writeJsTempFile ($scriptContent) {
        $scriptPath = PIMCORE_TEMPORARY_DIRECTORY."/minified_javascript_".md5($scriptContent).".js";
        
        if(!is_file($scriptPath)) {
            $scriptContent = $this->minify($scriptContent);
            file_put_contents($scriptPath, $scriptContent);
            chmod($scriptPath, 0766);
        }
        return $scriptPath;
    }
}

