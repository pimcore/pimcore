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

class Pimcore_Controller_Plugin_TagManagement extends Zend_Controller_Plugin_Abstract {

    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        $dir = OutputFilter_Tag_Config::getWorkingDir();

        $tags = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $name = str_replace(".xml", "", $file);
                $tags[] = OutputFilter_Tag_Config::getByName($name);
            }
        }



        include_once("simple_html_dom.php");
        $body = $this->getResponse()->getBody();

        $html = str_get_html($body);

        if($html) {

            foreach ($tags as $tag) {
                $method = strtolower($tag->getHttpMethod());
                $pattern = $tag->getUrlPattern();
                if( ($method == strtolower($this->getRequest()->getMethod()) || empty($method)) &&
                    (empty($pattern) || @preg_match($pattern, $this->getRequest()->getRequestUri()))
                ) {

                    if(is_array($tag->getItems())) {
                        foreach ($tag->getItems() as $item) {
                            if(!empty($item["element"]) && !empty($item["code"]) && !empty($item["position"])) {
                                $element = $html->find($item["element"],0);
                                if($element) {
                                    if($item["position"] == "end") {
                                        $element->innertext = $element->innertext . "\n\n" . $item["code"] . "\n\n";
                                    } else {
                                        // beginning
                                        $element->innertext = "\n\n" . $item["code"] . "\n\n" . $element->innertext;
                                    }
                                }
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

