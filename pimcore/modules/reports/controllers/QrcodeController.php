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

use Pimcore\Model\Tool\Qrcode;
use Pimcore\Model\Document;

class Reports_QrcodeController extends \Pimcore\Controller\Action\Admin\Reports {

    public function init() {
        parent::init();

        $notRestrictedActions = array("code");
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("qr_codes");
        }
    }

    public function treeAction () {

        $dir = Qrcode\Config::getWorkingDir();

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
            Qrcode\Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (\Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $code = new Qrcode\Config();
            $code->setName($this->getParam("name"));
            $code->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $code->getName()));
    }

    public function deleteAction () {

        $code = Qrcode\Config::getByName($this->getParam("name"));
        $code->delete();

        $this->_helper->json(array("success" => true));
    }


    public function getAction () {

        $code = Qrcode\Config::getByName($this->getParam("name"));
        $this->_helper->json($code);
    }


    public function updateAction () {

        $code = Qrcode\Config::getByName($this->getParam("name"));
        $data = \Zend_Json::decode($this->getParam("configuration"));
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

        if($this->getParam("name")) {
            $url = $this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost() . "/qr~-~code/" .
                $this->getParam("name");
        } else if ($this->getParam("documentId")) {
            $doc = Document::getById($this->getParam("documentId"));
            $url = $this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost()
                . $doc->getFullPath();
        } else if ($this->getParam("url")) {
            $url = $this->getParam("url");
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

        $code = \Pimcore\Image\Matrixcode::render('qrcode', $codeSettings, $renderer, $renderSettings);
    }
}

