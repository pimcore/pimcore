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

include_once("simple_html_dom.php");

class Pimcore_View_Helper_Glossary extends Zend_View_Helper_Abstract {

    public static $_controller;

    public static function getController() {
        if (!self::$_controller) {
            self::$_controller = new Pimcore_View_Helper_Glossary_Controller();
        }

        return self::$_controller;
    }

    public function glossary() {
        $controller = self::getController();
        $controller->setView($this->view);
        return $controller;
    }

}


class Pimcore_View_Helper_Glossary_Controller {

    /**
     * @var Pimcore_View
     */
    protected $view;


    public function start() {
        ob_start();
    }

    public function stop() {

        $contents = ob_get_clean();

        $data = $this->getData();
        //p_r($data);exit;
        
        $enabled = true;
        
        if(isset($_REQUEST["pimcore_editmode"])) {
            $enabled = false;
        }
        
        if (!empty($data) && $enabled) {
            // replace

            $blockedTags = array("a","script","style","code","pre","textarea","acronym","abbr","option","h1","h2","h3","h4","h5","h6");

            // why not using a simple str_ireplace(array(), array(), $subject) ?
            // because if you want to replace the terms "Donec vitae" and "Donec" you will get nested links, so the content of the html must be reloaded every searchterm to ensure that there is no replacement within a blocked tag


            // kind of a hack but,
            // changed to this because of that: http://www.pimcore.org/issues/browse/PIMCORE-687
            $html = str_get_html($contents);
            if(!$html) {
                return $contents;
            }

            $es = $html->find('text');

            $tmpData = array(
                "search" => array(),
                "replace" => array(),
                "placeholder" => array()
            );


            // get initial document out of the front controller (requested document, if it was a "document" request)
            $front = Zend_Controller_Front::getInstance();
            $currentDocument = $front->getRequest()->getParam("document");
            if(empty($currentDocument)) {
                $currentDocument = $this->view->document;
            }

            foreach ($data as $entry) {

                // check if the current document is the target link (id check)
                if($currentDocument instanceof Document && $entry["linkType"] == "internal" && $currentDocument->getId() == $entry["linkTarget"]) {
                    continue;
                }

                // check if the current document is the target link (path check)
                if($currentDocument instanceof Document && $currentDocument->getFullPath() == rtrim($entry["linkTarget"], " /")) {
                    continue;
                }


                $tmpData["search"][] = $entry["search"];
                $tmpData["replace"][] = $entry["replace"];
            }
            $data = $tmpData;

            $data["placeholder"] = array();
            for($i = 0; $i < count($data["search"]); $i++) {
                $data["placeholder"][] = '%%' . uniqid($i, true) . '%%';
            }

            foreach ($es as $e) {
                if(!in_array((string) $e->parent()->tag,$blockedTags)) {
                    $e->innertext = preg_replace($data["search"], $data["placeholder"], $e->innertext);
                    $e->innertext = str_replace($data["placeholder"], $data["replace"], $e->innertext);
                }
            }
            echo $html->save();

            // very memory intensive method with a huge amount of glossary entries
            /*foreach ($data as $search => $replace) {
                $html = str_get_html($contents);
                $es = $html->find('text');
                foreach ($es as $e) {
                    if(!in_array((string) $e->parent()->tag,$blockedTags)) {
                        $e->innertext = str_ireplace($search, $replace, $e->innertext);
                    }
                }
                $contents = $html->save();
            }
            echo $contents;*/
        }
        else {
            echo $contents;
        }
    }

    protected function getData() {

        if(Zend_Registry::isRegistered("Zend_Locale")) {
            $locale = (string) Zend_Registry::get("Zend_Locale");
        } else {
            return array();
        }

        $siteId = "";
        try {
            $site = Site::getCurrentSite();
            if($site instanceof Site) {
                $siteId = $site->getId();
            }
        } catch (Exception $e) {
            // not inside a site
        }

        $cacheKey = "glossary_" . $locale . "_" . $siteId;

        try {
            $data = Zend_Registry::get($cacheKey);
            return $data;
        }
        catch (Exception $e) {
        }


        if (!$data = Pimcore_Model_Cache::load($cacheKey)) {

            $list = new Glossary_List();
            $list->setCondition("(language = ? OR language IS NULL OR language = '') AND (site = ? OR site IS NULL OR site = '')", array($locale, $siteId));
            $list->setOrderKey("LENGTH(`text`)", false);
            $list->setOrder("DESC");
            $data = $list->getDataArray();

            $data = $this->prepareData($data);

            Pimcore_Model_Cache::save($data, $cacheKey, array("glossary"), null, 995);
            Zend_Registry::set($cacheKey, $data);
        }

        return $data;
    }

    protected function prepareData($data) {

        $mappedData = array();

        // fix htmlentities issues
        $tmpData = array();
        foreach ($data as $d) {
            if($d["text"] != htmlentities($d["text"],null,"UTF-8")) {
                $td = $d;
                $td["text"] = htmlentities($d["text"],null,"UTF-8");
                $tmpData[] = $td;
            }
            $tmpData[] = $d;
        }

        $data = $tmpData;

        // prepare data
        foreach ($data as $d) {

            if ($d["link"] || $d["abbr"] || $d["acronym"]) {

                $r = $d["text"];
                if ($d["abbr"]) {
                    $r = '<abbr class="pimcore_glossary" title="' . $d["abbr"] . '">' . $r . '</abbr>';
                }
                else if ($d["acronym"]) {
                    $r = '<acronym class="pimcore_glossary" title="' . $d["acronym"] . '">' . $r . '</acronym>';
                }

                $linkType = "";
                $linkTarget = "";

                if ($d["link"]) {

                    $linkType = "external";
                    $linkTarget = $d["link"];

                    if (intval($d["link"])) {
                        if ($doc = Document::getById($d["link"])) {
                            $d["link"] = $doc->getFullPath();
                            $linkType = "internal";
                            $linkTarget = $doc->getId();
                        }
                    }

                    $r = '<a class="pimc
                    ore_glossary" href="' . $d["link"] . '">' . $r . '</a>';
                }

                // add PCRE delimiter and modifiers
                if($d["exactmatch"]) {
                    $d["text"] = "/(?<!\w)" . preg_quote($d["text"],"/") . "(?!\w)/";
                } else {
                    $d["text"] = "/" . preg_quote($d["text"],"/") . "/";
                }

                if(!$d["casesensitive"]) {
                    $d["text"] .= "i";
                }

                $mappedData[] = array(
                    "replace" => $r,
                    "search" => $d["text"],
                    "linkType" => $linkType,
                    "linkTarget" => $linkTarget
                );
            }
        }

        return $mappedData;
    }

    /**
     * @param \Pimcore_View $view
     */
    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * @return \Pimcore_View
     */
    public function getView()
    {
        return $this->view;
    }
}
