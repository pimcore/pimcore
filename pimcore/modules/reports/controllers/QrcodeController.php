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

class Reports_QrcodeController extends Pimcore_Controller_Action_Admin_Reports {

    public function treeAction () {

        $dir = Tool_Qrcode_Config::getWorkingDir();

        $codes = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $name = str_replace(".xml", "", $file);
                $codes[] = array(
                    "id" => $name,
                    "text" => $name
                );
            }
        }

        $this->_helper->json($codes);
    }

    public function addAction () {

        try {
            Tool_Qrcode_Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $code = new Tool_Qrcode_Config();
            $code->setName($this->getParam("name"));
            $code->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $code->getName()));
    }

    public function deleteAction () {

        $code = Tool_Qrcode_Config::getByName($this->getParam("name"));
        $code->delete();

        $this->_helper->json(array("success" => true));
    }


    public function getAction () {

        $code = Tool_Qrcode_Config::getByName($this->getParam("name"));
        $this->_helper->json($code);
    }


    public function updateAction () {

        $code = Tool_Qrcode_Config::getByName($this->getParam("name"));
        $data = Zend_Json::decode($this->getParam("configuration"));
        $data = array_htmlspecialchars($data);

        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if(method_exists($code, $setter)) {
                $code->$setter($value);
            }
        }

        $code->save();

        $this->_helper->json(array("success" => true));
    }

    public function codeAction () {

        $url = $this->getParam("url");
        $urlParts = parse_url($url);

        if(empty($urlParts["host"])) {
            $urlParts["host"] = $this->getRequest()->getHttpHost();
        }
        if(empty($urlParts["scheme"])) {
            $urlParts["scheme"] = $this->getRequest()->getScheme();
        }

        if($this->getParam("googleAnalytics") === "true") {
            if(!array_key_exists("query", $urlParts)) {
                $urlParts["query"] = "";
            } else {
                $urlParts["query"] .= "&";
            }

            $urlParts["query"] .= "utm_source=Mobile&utm_medium=QR-Code&utm_campaign=" . $this->getParam("name");
        }

        $url = $urlParts["scheme"] . "://" . $urlParts["host"] . $urlParts["path"];
        if(!empty($urlParts["query"])) {
            $url .= "?" . $urlParts["query"];
        }
        if(!empty($urlParts["fragment"])) {
            $url .= ("#" . $urlParts["fragment"]);
        }

        $codeSettings = array(
            'text' => $url,
            'backgroundColor' => '#FFFFFF',
            'foreColor' => '#000000',
            'padding' => 0,  //array(10,5,10,5),
            'moduleSize' => 10
        );

        $extension = $this->getParam("renderer");
        if($extension == "image") {
            $extension ="png";
        }

        $renderSettings = array();
        if($this->getParam("download")) {
            $renderSettings["sendResult"] = array('Content-Disposition: attachment;filename="qrcode-' . $this->getParam("name") . '.' . $extension . '"');
        }

        foreach ($this->getAllParams() as $key => $value) {
            if(array_key_exists($key, $codeSettings) && !empty($value)) {
                if(stripos($key, "color")) {
                    if(strlen($value) == 7) {
                        $value = strtoupper($value);
                        $codeSettings[$key] = $value;
                    }
                } else {
                    $codeSettings[$key] = $value;
                }
            }
            if(array_key_exists($key, $renderSettings) && !empty($value)) {
                $renderSettings[$key] = $value;
            }
        }

        $renderer = "image";
        if($this->getParam("renderer") && in_array($this->getParam("renderer"), array("pdf", "image", "eps", "svg"))) {
            $renderer = $this->getParam("renderer");
        }

        $code = Pimcore_Image_Matrixcode::render('qrcode', $codeSettings, $renderer, $renderSettings);
    }
}

