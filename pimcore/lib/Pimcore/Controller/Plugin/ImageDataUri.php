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

class Pimcore_Controller_Plugin_ImageDataUri extends Zend_Controller_Plugin_Abstract {

    protected $supported = false;
    protected $enabled = false;

    public function routeStartup(Zend_Controller_Request_Abstract $request) {
        
        $conf = Pimcore_Config::getSystemConfig();
        if (!$conf->outputfilters) {
            return $this->disable();
        }

        if (!$conf->outputfilters->imagedatauri) {
            return $this->disable();
        }

        //detect browser
        $browser = new Pimcore_Browser();  
        $browserVersion = (int) $browser->getVersion();
                
        if ($browser->getBrowser() == "Firefox" && $browserVersion >= 3) {
            $this->supported = true;
        }
        else if ($browser->getBrowser() == "Internet Explorer" && $browserVersion >= 8) {
            $this->supported = true;
        }
        else if ($browser->getBrowser() == "Chrome" && $browserVersion >= 5) {
            $this->supported = true;
        }
        else if ($browser->getBrowser() == "Safari" && $browserVersion >= 4) {
            $this->supported = true;
        }
        else {
            return $this->disable();
        }          

        // set cache key suffix for outputcache
        if ($this->supported) {
            $this->enabled = true;

            if (!$tags = $request->getParam("pimcore_cache_tag_suffix")) {
                $tags = array();
            }
            $tags[] = "datauri";
            $request->setParam("pimcore_cache_tag_suffix", $tags);
        }
    }
    
    public function disable() {
        $this->enabled = false;
        $this->supported = false;
        return true;
    }

    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        if ($this->supported && $this->enabled) {

            include_once("simple_html_dom.php");
            $body = $this->getResponse()->getBody();

            $html = str_get_html($body);

            if($html) {
                $images = $html->find("img");

                foreach ($images as $image) {
                    $source = $image->src;
                    $path = null;

                    if (strpos($source, "http") === false) {
                        // check asset folder
                        if (is_file(PIMCORE_ASSET_DIRECTORY . $source)) {
                            $path = PIMCORE_ASSET_DIRECTORY . $source;
                        }
                        else if (is_file(PIMCORE_DOCUMENT_ROOT . $source)) {
                            $path = PIMCORE_DOCUMENT_ROOT . $source;
                        }

                        if (is_file($path)) {
                            if (@filesize($path) < 20000) { // only files < 20k because of IE8, 20000 because it's better to be a little bit under the limit

                                try {
                                    $mimetype = MIME_Type::autoDetect($path);
                                    if (is_string($mimetype)) {
                                        $image->src = 'data:' . $mimetype . ';base64,' . base64_encode(file_get_contents($path));
                                    }
                                }
                                catch (Exception $e) {
                                }
                            }
                        }
                    }
                }

                $body = $html->save();
                $this->getResponse()->setBody($body);
            }
        }
    }
}

