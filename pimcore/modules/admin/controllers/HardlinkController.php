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
            $link->sourcePath = $link->getSourceDocument()->getFullpath();
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

        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document\Hardlink $link)
    {

        // data
        $data = \Zend_Json::decode($this->getParam("data"));

        $sourceId = null;
        if ($sourceDocument = Document::getByPath($data["sourcePath"])) {
            $sourceId = $sourceDocument->getId();
        }
        $link->setSourceId($sourceId);

        $link->setValues($data);
        $this->addPropertiesToDocument($link);
    }
}
