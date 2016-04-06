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

use Pimcore\Tool;
use Pimcore\Model\Tool\Newsletter;
use Pimcore\Model\Document;
use Pimcore\Model\Object;

class Reports_NewsletterController extends \Pimcore\Controller\Action\Admin\Reports
{

    public function init()
    {
        parent::init();

        $this->checkPermission("newsletter");
    }

    public function treeAction()
    {
        $letters = [];

        $list = new Newsletter\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $letters[] = array(
                "id" => $item->getName(),
                "text" => $item->getName()
            );
        }

        $this->_helper->json($letters);
    }

    public function addAction()
    {
        $success = false;

        $letter = Newsletter\Config::getByName($this->getParam("name"));

        if (!$letter) {
            $letter = new Newsletter\Config();
            $letter->setName($this->getParam("name"));
            $letter->save();

            $success = true;
        }

        $this->_helper->json(array("success" => $success, "id" => $letter->getName()));
    }

    public function deleteAction()
    {
        $letter = Newsletter\Config::getByName($this->getParam("name"));
        $letter->delete();

        $this->_helper->json(array("success" => true));
    }


    public function getAction()
    {
        $letter = Newsletter\Config::getByName($this->getParam("name"));

        if ($emailDoc = Document::getById($letter->getDocument())) {
            $letter->setDocument($emailDoc->getRealFullPath());
        }

        // get available classes
        $classList = new Object\ClassDefinition\Listing();

        $availableClasses = array();
        foreach ($classList->load() as $class) {
            $fieldCount = 0;
            foreach ($class->getFieldDefinitions() as $fd) {
                if ($fd instanceof Object\ClassDefinition\Data\NewsletterActive ||
                $fd instanceof Object\ClassDefinition\Data\NewsletterConfirmed ||
                $fd instanceof Object\ClassDefinition\Data\Email) {
                    $fieldCount++;
                }
            }

            if ($fieldCount >= 3) {
                $availableClasses[] = array($class->getName(), $class->getName());
            }
        }


        $letter->availableClasses = $availableClasses;

        $this->_helper->json($letter);
    }


    public function updateAction()
    {
        $letter = Newsletter\Config::getByName($this->getParam("name"));
        $data = \Zend_Json::decode($this->getParam("configuration"));

        if ($emailDoc = Document::getByPath($data["document"])) {
            $data["document"] = $emailDoc->getId();
        }

        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if (method_exists($letter, $setter)) {
                $letter->$setter($value);
            }
        }

        $letter->save();

        $this->_helper->json(array("success" => true));
    }

    public function checksqlAction()
    {
        $count = 0;
        $success = false;
        try {
            $className = "\\Pimcore\\Model\\Object\\" . ucfirst($this->getParam("class")) . "\\Listing";
            $list = new $className();

            $conditions = array("(newsletterActive = 1 AND newsletterConfirmed = 1)");
            if ($this->getParam("objectFilterSQL")) {
                $conditions[] = $this->getParam("objectFilterSQL");
            }
            $list->setCondition(implode(" AND ", $conditions));

            $count = $list->getTotalCount();
            $success = true;
        } catch (\Exception $e) {
        }

        $this->_helper->json(array(
            "count" => $count,
            "success" => $success
        ));
    }

    public function getSendStatusAction()
    {
        $letter = Newsletter\Config::getByName($this->getParam("name"));
        $data = null;
        if (file_exists($letter->getPidFile())) {
            $data = Tool\Serialize::unserialize(file_get_contents($letter->getPidFile()));
        }

        $this->_helper->json(array(
            "data" => $data,
            "success" => true
        ));
    }

    public function stopSendAction()
    {
        $letter = Newsletter\Config::getByName($this->getParam("name"));
        if (file_exists($letter->getPidFile())) {
            @unlink($letter->getPidFile());
        }

        $this->_helper->json(array(
            "success" => true
        ));
    }

    public function sendAction()
    {
        $letter = Newsletter\Config::getByName($this->getParam("name"));
        if ($letter) {
            $cmd = Tool\Console::getPhpCli() . " " . realpath(PIMCORE_PATH . DIRECTORY_SEPARATOR . "cli" . DIRECTORY_SEPARATOR . "console.php"). " internal:newsletter-send " . escapeshellarg($letter->getName()) . " " . escapeshellarg(Tool::getHostUrl());
            Tool\Console::execInBackground($cmd, PIMCORE_LOG_DIRECTORY . "/newsletter--" . $letter->getName() . ".log");
        }

        $this->_helper->json(array("success" => true));
    }

    public function sendTestAction()
    {
        $letter = Newsletter\Config::getByName($this->getParam("name"));

        $className = "\\Pimcore\\Model\\Object\\" . ucfirst($letter->getClass());

        $object = $className::getByEmail($letter->getTestEmailAddress(), 1);
        if (!$object) {
            $objectList = $className . "\\Listing";
            $list = new $objectList();

            if ($letter->getObjectFilterSQL()) {
                $list->setCondition($letter->getObjectFilterSQL());
            }

            $list->setOrderKey("RAND()", false);
            $list->setLimit(1);
            $list->setOffset(0);

            $object = current($list->load());
            if (!$object) {
                throw new \Exception("no valid user data available, can't send email");
            }
        }

        Tool\Newsletter::sendMail($letter, $object, $letter->getTestEmailAddress());

        $this->_helper->json(array("success" => true));
    }
}
