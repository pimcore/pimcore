<?php

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
        return self::getController();
    }

}


class Pimcore_View_Helper_Glossary_Controller {

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

            $blockedTags = array("a","script","style","code","pre","textarea","acronym","abbr","option");

            // why not using a simple str_ireplace(array(), array(), $subject) ?
            // because if you want to replace the terms "Donec vitae" and "Donec" you will get nested links, so the content of the html must be reloaded every searchterm to ensure that there is no replacement within a blocked tag


            // kind of a hack but,
            // changed to this because of that: http://www.pimcore.org/issues/browse/PIMCORE-687
            $html = str_get_html($contents);
            if(!$html) {
                return $contents;
            }

            $es = $html->find('text');

            $tmpData = array();
            foreach ($data as $search => $replace) {
                $tmpData["search"][] = $search;
                $tmpData["replace"][] = $replace;
            }
            $data = $tmpData;

            $data["placeholder"] = array();
            for($i = 0; $i < count($data["search"]); $i++) {
                $data["placeholder"][] = '%%' . uniqid($i, true) . '%%';
            }

            foreach ($es as $e) {
                if(!in_array((string) $e->parent()->tag,$blockedTags)) {
                    $e->innertext = str_ireplace($data["search"], $data["placeholder"], $e->innertext);
                    $e->innertext = str_ireplace($data["placeholder"], $data["replace"], $e->innertext);
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

        try {
            $locale = Zend_Registry::get("Zend_Locale");
        }
        catch (Exception $e) {
            return array();
        }

        $cacheKey = "glossary_" . $locale->getLanguage();

        try {
            $data = Zend_Registry::get($cacheKey);
            return $data;
        }
        catch (Exception $e) {
        }


        if (!$data = Pimcore_Model_Cache::load($cacheKey)) {

            $list = new Glossary_List();
            $list->setCondition("language = ?", $locale->getLanguage());
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

                if ($d["link"]) {

                    if (intval($d["link"])) {
                        if ($doc = Document::getById($d["link"])) {
                            $d["link"] = $doc->getFullPath();
                        }
                    }

                    $r = '<a class="pimcore_glossary" href="' . $d["link"] . '">' . $r . '</a>';
                }

                $mappedData[$d["text"]] = $r;
            }
        }

        return $mappedData;
    }
}
