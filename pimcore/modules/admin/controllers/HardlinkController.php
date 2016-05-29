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

use Pimcore\Model\Document;
use Pimcore\Model\Element;

class Admin_HardlinkController extends \Pimcore\Controller\Action\Admin\Document
{

    public function getDataByIdAction()
    {

        // check for lock
        if (Element\Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "document")
            ));
        }
        Element\Editlock::lock($this->getParam("id"), "document");

        $link = Document\Hardlink::getById($this->getParam("id"));
        $link = clone $link;

        $link->idPath = Element\Service::getIdPath($link);
        $link->userPermissions = $link->getUserPermissions();
        $link->setLocked($link->isLocked());
        $link->setParent(null);

        if ($link->getSourceDocument()) {
            $link->sourcePath = $link->getSourceDocument()->getRealFullPath();
        }

        $this->addTranslationsData($link);
        $this->minimizeProperties($link);

        if ($link->isAllowed("view")) {
            $this->_helper->json($link);
        }

        $this->_helper->json(false);
    }

    public function saveAction()
    {
        try {
            if ($this->getParam("id")) {
                $link = Document\Hardlink::getById($this->getParam("id"));
                $this->setValuesToDocument($link);

                $link->setModificationDate(time());
                $link->setUserModification($this->getUser()->getId());

                if ($this->getParam("task") == "unpublish") {
                    $link->setPublished(false);
                }
                if ($this->getParam("task") == "publish") {
                    $link->setPublished(true);
                }

                // only save when publish or unpublish
                if (($this->getParam("task") == "publish" && $link->isAllowed("publish")) || ($this->getParam("task") == "unpublish" && $link->isAllowed("unpublish"))) {
                    $link->save();

                    $this->_helper->json(array("success" => true));
                }
            }
        } catch (\Exception $e) {
            \Logger::log($e);
            if (\Pimcore\Tool\Admin::isExtJS6() && $e instanceof Element\ValidationException) {
                $this->_helper->json(array("success" => false, "type" => "ValidationException", "message" => $e->getMessage(), "stack" => $e->getTraceAsString(), "code" => $e->getCode()));
            }
            throw $e;
        }

        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document\Hardlink $link)
    {

        // data
        if ($this->getParam("data")) {
            $data = \Zend_Json::decode($this->getParam("data"));

            $sourceId = null;
            if ($sourceDocument = Document::getByPath($data["sourcePath"])) {
                $sourceId = $sourceDocument->getId();
            }
            $link->setSourceId($sourceId);
            $link->setValues($data);
        }

        $this->addPropertiesToDocument($link);
    }
}
