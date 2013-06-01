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
        if(!Pimcore_Tool::useFrontendOutputFilters($request) || /*!$config->general->targeting*/ !PIMCORE_DEVMODE) {
            return $this->disable();
        }
        
        if($request->getParam("document") instanceof Document_Page) {
            $this->document = $request->getParam("document");
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
            $personas = array();
            $dataPush = array();

            if(count($this->events) > 0) {
                $dataPush["events"] = $this->events;
            }

            if($this->document instanceof Document_Page) {
                $dataPush["document"] = $this->document->getId();
                if($this->document->getPersonas()) {
                    if($_GET["_ptp"]) { // if a special version is requested only return this id as target group for this page
                        $dataPush["personas"] = array((int)$_GET["_ptp"]);
                    } else {
                        $docPersonas = explode(",", trim($this->document->getPersonas(), " ,"));

                        //  cast the values to int
                        array_walk($docPersonas, function (&$value) {
                            $value = (int) trim($value);
                        });
                        $dataPush["personas"] = $docPersonas;
                    }
                }

                // check for persona specific variants of this page
                $personaVariants = array();
                foreach($this->document->getElements() as $key => $tag) {
                    if(preg_match("/^persona_-([0-9]+)-_/", $key, $matches)) {
                        $personaVariants[] = (int) $matches[1];
                    }
                }

                if(!empty($personaVariants)) {
                    $personaVariants = array_unique($personaVariants);
                    $dataPush["personaPageVariants"] = $personaVariants;
                }
            }

            if($this->document) {
                // @TODO: cache this
                $list = new Tool_Targeting_Rule_List();

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

                $list = new Tool_Targeting_Persona_List();
                foreach($list->load() as $persona) {
                    $personas[] = $persona;
                }
            }

            if(!($controlCode = Pimcore_Model_Cache::load("targeting_control_code")) || PIMCORE_DEVMODE) {
                $controlCode = file_get_contents(PIMCORE_PATH . "/static/js/frontend/targeting.js");
                $controlCode = JSMinPlus::minify($controlCode);

                Pimcore_Model_Cache::save($controlCode, "targeting_control_code", array("output"), null, 999);
            }

            $code = '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
            $code .= '<script type="text/javascript">';
                $code .= 'var pimcore = pimcore || {};';
                $code .= 'pimcore["targeting"] = {};';
                $code .= 'pimcore["targeting"]["dataPush"] = ' . Zend_Json::encode($dataPush) . ';';
                $code .= 'pimcore["targeting"]["targets"] = ' . Zend_Json::encode($targets) . ';';
                $code .= 'pimcore["targeting"]["personas"] = ' . Zend_Json::encode($personas) . ';';
            $code .= '</script>';
            $code .= '<script type="text/javascript">' . $controlCode . '</script>' . "\n";
            // analytics
            $body = $this->getResponse()->getBody();

            // search for the end <head> tag, and insert the google analytics code before
            // this method is much faster than using simple_html_dom and uses less memory
            $headEndPosition = stripos($body, "<head>");
            if($headEndPosition !== false) {
                $body = substr_replace($body, "<head>\n".$code, $headEndPosition, 7);
            }

            $this->getResponse()->setBody($body);
        }
    }
}
