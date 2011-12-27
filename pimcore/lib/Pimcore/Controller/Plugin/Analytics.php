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

class Pimcore_Controller_Plugin_Analytics extends Zend_Controller_Plugin_Abstract {

    protected $enabled = true;
    protected $document;

    public function routeShutdown(Zend_Controller_Request_Abstract $request) {
        if(!Pimcore_Tool::useFrontendOutputFilters($request)) {
            return $this->disable();
        }
        
        if($request->getParam("document") instanceof Document_Page) {
            $this->document = $request->getParam("document");
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
        
        if ($this->enabled && $code = Pimcore_Google_Analytics::getCode()) {
            
            // analytics
            include_once("simple_html_dom.php");
            $body = $this->getResponse()->getBody();


            // search for the end <head> tag, and insert the google analytics code before
            // this method is much faster than using simple_html_dom and uses less memory
            $headEndPosition = strpos($body, "</head>");
            if($headEndPosition !== false) {
                $body = substr_replace($body, $code."</head>", $headEndPosition, 7);
            }

            // website optimizer
            if($this->document) {
                $top = Pimcore_Google_Analytics::getOptimizerTop($this->document);
                $bottom = Pimcore_Google_Analytics::getOptimizerBottom($this->document);
                $conversion = Pimcore_Google_Analytics::getOptimizerConversion($this->document);

                if($top || $bottom || $conversion) {
                    $html = str_get_html($body);

                    if($html) {
                        $body = $html->find("body",0);

                        if($top && $bottom) {
                            $body->innertext = $top . $body->innertext . $bottom;
                        }
                        else if ($conversion) {
                            $body->innertext = $body->innertext . $conversion;
                        }
                        else if($bottom) {
                            $body->innertext = $body->innertext . $bottom;
                        }

                        $body = $html->save();
                    }
                }
            }



            $this->getResponse()->setBody($body);


        }
    }
}
