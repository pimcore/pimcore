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

class Pimcore_Controller_Plugin_Targeting extends Zend_Controller_Plugin_Abstract {

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var Document
     */
    protected $document;

    /**
     * @var array
     */
    protected $events = array();

    /**
     * @param $key
     * @param $value
     */
    public function addEvent($key, $value) {
        $this->events[] = array("key" => $key, "value" => $value);
    }

    /**
     * @param Zend_Controller_Request_Abstract $request
     * @return bool|void
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request) {

        $config = Pimcore_Config::getSystemConfig();
        if(!Pimcore_Tool::useFrontendOutputFilters($request) || !$config->general->targeting) {
            return $this->disable();
        }
        
        if($request->getParam("document") instanceof Document_Page) {
            $this->document = $request->getParam("document");
        } else {
            $this->disable();
        }
    }

    /**
     * @return bool
     */
    public function disable() {
        $this->enabled = false;
        return true;
    }

    /**
     *
     */
    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        if ($this->enabled) {

            $targets = array();

            if($this->document) {
                $list = new Tool_Targeting_List();
                $list->setCondition("documentId = ? OR documentId = '' OR documentId IS NULL", $this->document->getId());

                foreach($list->load() as $target) {

                    $redirectUrl = $target->getActions()->getRedirectUrl();
                    if(is_numeric($redirectUrl)) {
                        $doc = Document::getById($redirectUrl);
                        if($doc instanceof Document) {
                            $target->getActions()->redirectUrl = $doc->getFullPath();
                        }
                    }

                    $targets[] = $target;
                }
            }

            if(!($controlCode = Pimcore_Model_Cache::load("targeting_control_code")) || PIMCORE_DEVMODE) {
                $controlCode = file_get_contents(__DIR__ . "/Targeting/targeting.js");
                $controlCode = JSMinPlus::minify($controlCode);

                Pimcore_Model_Cache::save($controlCode, "targeting_control_code", array("output"), null, 999);
            }

            $code = '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
            $code .= '<script type="text/javascript">var _ptd = ' . Zend_Json::encode($targets) . '</script>';
            $code .= '<script type="text/javascript">' . $controlCode . '</script>' . "\n";
            // analytics
            $body = $this->getResponse()->getBody();

            // search for the end <head> tag, and insert the google analytics code before
            // this method is much faster than using simple_html_dom and uses less memory
            $headEndPosition = strpos($body, "<head>");
            if($headEndPosition !== false) {
                $body = substr_replace($body, "<head>\n".$code, $headEndPosition, 7);
            }

            $this->getResponse()->setBody($body);

            if(count($this->events) > 0) {
                setcookie("pimcore__~__targeting", Zend_Json::encode($this->events));
            }
        }
    }
}
