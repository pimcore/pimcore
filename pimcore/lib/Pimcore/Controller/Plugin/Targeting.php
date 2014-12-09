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

use Pimcore\Tool;
use Pimcore\Model\Document;
use Pimcore\Model;

class Targeting extends \Zend_Controller_Plugin_Abstract {

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
     * @var array
     */
    protected $personas = array();


    /**
     * @param $key
     * @param $value
     */
    public function addEvent($key, $value) {
        $this->events[] = array("key" => $key, "value" => $value);
    }

    /**
     * @param $id
     */
    public function addPersona($id) {
        $this->personas[] = $id;
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return bool|void
     */
    public function routeShutdown(\Zend_Controller_Request_Abstract $request) {

        if(!Tool::useFrontendOutputFilters($request)) {
            return $this->disable();
        }

        $db = \Pimcore\Resource::get();
        $enabled = $db->fetchOne("SELECT id FROM targeting_personas UNION SELECT id FROM targeting_rules LIMIT 1");
        if(!$enabled) {
            return $this->disable();
        }

        if($request->getParam("document") instanceof Document\Page) {
            $this->document = $request->getParam("document");
        }
    }

    /**
     * @return $this
     */
    public function enable() {
        $this->enabled = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disable() {
        $this->enabled = false;
        return $this;
    }

    /**
     *
     */
    public function dispatchLoopShutdown() {

        if(!Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        if ($this->enabled) {

            $targets = array();
            $personas = array();
            $dataPush = array(
                "personas" => $this->personas,
                "method" => strtolower($this->getRequest()->getMethod())
            );

            if(count($this->events) > 0) {
                $dataPush["events"] = $this->events;
            }

            if($this->document instanceof Document\Page && !Model\Staticroute::getCurrentRoute()) {
                $dataPush["document"] = $this->document->getId();
                if($this->document->getPersonas()) {
                    if($_GET["_ptp"]) { // if a special version is requested only return this id as target group for this page
                        $dataPush["personas"][] = (int) $_GET["_ptp"];
                    } else {
                        $docPersonas = explode(",", trim($this->document->getPersonas(), " ,"));

                        //  cast the values to int
                        array_walk($docPersonas, function (&$value) {
                            $value = (int) trim($value);
                        });
                        $dataPush["personas"] = array_merge($dataPush["personas"], $docPersonas);
                    }
                }

                // check for persona specific variants of this page
                $personaVariants = array();
                foreach($this->document->getElements() as $key => $tag) {
                    if(preg_match("/^persona_-([0-9]+)-_/", $key, $matches)) {
                        $id = (int) $matches[1];
                        if(Model\Tool\Targeting\Persona::isIdActive($id)) {
                            $personaVariants[] = $id;
                        }
                    }
                }

                if(!empty($personaVariants)) {
                    $personaVariants = array_values(array_unique($personaVariants));
                    $dataPush["personaPageVariants"] = $personaVariants;
                }
            }

            // no duplicates
            $dataPush["personas"] = array_unique($dataPush["personas"]);
            $activePersonas = array();
            foreach ($dataPush["personas"] as $id) {
                if(Model\Tool\Targeting\Persona::isIdActive($id)) {
                    $activePersonas[] = $id;
                }
            }
            $dataPush["personas"] = $activePersonas;


            if($this->document) {
                // @TODO: cache this
                $list = new Model\Tool\Targeting\Rule\Listing();
                $list->setCondition("active = 1");

                foreach($list->load() as $target) {

                    $redirectUrl = $target->getActions()->getRedirectUrl();
                    if(is_numeric($redirectUrl)) {
                        $doc = \Document::getById($redirectUrl);
                        if($doc instanceof \Document) {
                            $target->getActions()->redirectUrl = $doc->getFullPath();
                        }
                    }

                    $targets[] = $target;
                }

                $list = new Model\Tool\Targeting\Persona\Listing();
                $list->setCondition("active = 1");
                foreach($list->load() as $persona) {
                    $personas[] = $persona;
                }
            }



            $code = '<script type="text/javascript" src="/pimcore/static/js/frontend/geoip.js/"></script>';
            $code .= '<script type="text/javascript">';
                $code .= 'var pimcore = pimcore || {};';
                $code .= 'pimcore["targeting"] = {};';
                $code .= 'pimcore["targeting"]["dataPush"] = ' . \Zend_Json::encode($dataPush) . ';';
                $code .= 'pimcore["targeting"]["targetingRules"] = ' . \Zend_Json::encode($targets) . ';';
                $code .= 'pimcore["targeting"]["personas"] = ' . \Zend_Json::encode($personas) . ';';
            $code .= '</script>';
            $code .= '<script type="text/javascript" src="/pimcore/static/js/frontend/targeting.js"></script>';
            $code .= "\n";
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
