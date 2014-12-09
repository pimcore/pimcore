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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Controller\Plugin;

use Pimcore\Model\Cache;
use Pimcore\Model\Tool;

class TagManagement extends \Zend_Controller_Plugin_Abstract {

    /**
     *
     */
    public function dispatchLoopShutdown() {
        
        if(!\Pimcore\Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        $cacheKey = "outputfilter_tagmngt";
        $tags = Cache::load($cacheKey);
        if(!is_array($tags)) {
            $dir = Tool\Tag\Config::getWorkingDir();

            $tags = array();
            $files = scandir($dir);
            foreach ($files as $file) {
                if(strpos($file, ".xml")) {
                    $name = str_replace(".xml", "", $file);
                    $tags[] = Tool\Tag\Config::getByName($name);
                }
            }
            Cache::save($tags, $cacheKey, array("tagmanagement"), null, 100);
        }

        if(empty($tags)) {
            return;
        }


        $html = null;
        $body = $this->getResponse()->getBody();
        $requestParams = array_merge($_GET, $_POST);


        foreach ($tags as $tag) {
            $method = strtolower($tag->getHttpMethod());
            $pattern = $tag->getUrlPattern();
            $textPattern = $tag->getTextPattern();

            // site check
            if(\Site::isSiteRequest() && $tag->getSiteId()) {
                if(\Site::getCurrentSite()->getId() != $tag->getSiteId()) {
                    continue;
                }
            } else if (!\Site::isSiteRequest() && $tag->getSiteId()) {
                continue;
            }

            $requestPath = rtrim($this->getRequest()->getPathInfo(),"/");

            if( ($method == strtolower($this->getRequest()->getMethod()) || empty($method)) &&
                (empty($pattern) || @preg_match($pattern, $requestPath)) &&
                (empty($textPattern) || strpos($body,$textPattern) !== false)
            ) {

                $paramsValid = true;
                foreach ($tag->getParams() as $param) {
                    if(!empty($param["name"])) {
                        if(!empty($param["value"])) {
                            if(!array_key_exists($param["name"], $requestParams) || $requestParams[$param["name"]] != $param["value"]) {
                                $paramsValid = false;
                            }
                        } else {
                            if(!array_key_exists($param["name"], $requestParams)) {
                                $paramsValid = false;
                            }
                        }
                    }
                }

                if(is_array($tag->getItems()) && $paramsValid) {
                    foreach ($tag->getItems() as $item) {
                        if(!empty($item["element"]) && !empty($item["code"]) && !empty($item["position"])) {

                            if(!$html) {
                                include_once("simple_html_dom.php");
                                $html = str_get_html($body);
                            }

                            if($html) {
                                $element = $html->find($item["element"],0);
                                if($element) {
                                    if($item["position"] == "end") {
                                        $element->innertext = $element->innertext . "\n\n" . $item["code"] . "\n\n";
                                    } else {
                                        // beginning
                                        $element->innertext = "\n\n" . $item["code"] . "\n\n" . $element->innertext;
                                    }

                                    // we havve to reinitialize the html object, otherwise it causes problems with nested child selectors
                                    $body = $html->save();

                                    $html->clear();
                                    unset($html);

                                    $html = null;
                                }
                            }
                        }
                    }
                }
            }
        }

        if($html && method_exists($html, "clear")) {
            $html->clear();
            unset($html);
        }

        $this->getResponse()->setBody($body);
    }
}

