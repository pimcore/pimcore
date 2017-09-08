<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Model\Tool\Qrcode;
use Pimcore\Model\Document;

class Reports_QrcodeController extends \Pimcore\Controller\Action\Admin\Reports
{
    public function init()
    {
        parent::init();

        $notRestrictedActions = ["code"];
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("qr_codes");
        }
    }

    public function treeAction()
    {
        $codes = [];

        $list = new Qrcode\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $codes[] = [
                "id" => $item->getName(),
                "text" => $item->getName()
            ];
        }

        $this->_helper->json($codes);
    }

    public function addAction()
    {
        $success = false;

        $code = Qrcode\Config::getByName($this->getParam("name"));

        if (!$code) {
            $code = new Qrcode\Config();
            $code->setName($this->getParam("name"));
            $code->save();

            $success = true;
        }

        $this->_helper->json(["success" => $success, "id" => $code->getName()]);
    }

    public function deleteAction()
    {
        $code = Qrcode\Config::getByName($this->getParam("name"));
        $code->delete();

        $this->_helper->json(["success" => true]);
    }


    public function getAction()
    {
        $code = Qrcode\Config::getByName($this->getParam("name"));
        $this->_helper->json($code);
    }


    public function updateAction()
    {
        $code = Qrcode\Config::getByName($this->getParam("name"));
        $data = \Zend_Json::decode($this->getParam("configuration"));

        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if (method_exists($code, $setter)) {
                $code->$setter($value);
            }
        }

        $code->save();

        $this->_helper->json(["success" => true]);
    }

    public function codeAction()
    {
        $url = "";

        if ($this->getParam("name")) {
            $url = $this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost() . "/qr~-~code/" .
                $this->getParam("name");
        } elseif ($this->getParam("documentId")) {
            $doc = Document::getById($this->getParam("documentId"));
            $url = $this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost()
                . $doc->getFullPath();
        } elseif ($this->getParam("url")) {
            $url = $this->getParam("url");
        }

        $code = new \Endroid\QrCode\QrCode;
        $code->setText($url);
        $code->setSize(500);

        $hexToRGBA = function ($hex) {
            list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");

            return ["r" => $r, "g" => $g, "b" => $b, "a" => 0];
        };

        if (strlen($this->getParam("foreColor", "")) == 7) {
            $code->setForegroundColor($hexToRGBA($this->getParam("foreColor")));
        }

        if (strlen($this->getParam("backgroundColor", "")) == 7) {
            $code->setBackgroundColor($hexToRGBA($this->getParam("backgroundColor")));
        }

        header("Content-Type: image/png");
        if ($this->getParam("download")) {
            $code->setSize(4000);
            header('Content-Disposition: attachment;filename="qrcode-' . $this->getParam("name", "preview") . '.png"', true);
        }

        echo $code->writeString();

        exit;
    }
}
