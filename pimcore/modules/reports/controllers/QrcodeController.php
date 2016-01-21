<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
        $codes = [];

        $list = new Qrcode\Config\Listing();
        $items = $list->load();

        foreach($items as $item) {
            $codes[] = array(
                "id" => $item->getName(),
                "text" => $item->getName()
            );
        }

        $this->_helper->json($codes);
    }

    public function addAction () {

        $success = false;

        $code = Qrcode\Config::getByName($this->getParam("name"));

        if(!$code) {
            $code = new Qrcode\Config();
            $code->setName($this->getParam("name"));
            $code->save();

            $success = true;
        }

        $this->_helper->json(array("success" => $success, "id" => $code->getName()));
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

