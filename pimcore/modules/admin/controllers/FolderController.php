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
use Pimcore\Logger;

class Admin_FolderController extends \Pimcore\Controller\Action\Admin\Document
{
    public function getDataByIdAction()
    {

        // check for lock
        if (Element\Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json([
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "document")
            ]);
        }
        Element\Editlock::lock($this->getParam("id"), "document");

        $folder = Document\Folder::getById($this->getParam("id"));
        $folder = clone $folder;

        $folder->idPath = Element\Service::getIdPath($folder);
        $folder->userPermissions = $folder->getUserPermissions();
        $folder->setLocked($folder->isLocked());
        $folder->setParent(null);

        $this->addTranslationsData($folder);
        $this->minimizeProperties($folder);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $returnValueContainer = new \Pimcore\Model\Tool\Admin\EventDataContainer(object2array($folder));
        \Pimcore::getEventManager()->trigger("admin.document.get.preSendData", $this, [
            "document" => $folder,
            "returnValueContainer" => $returnValueContainer
        ]);

        if ($folder->isAllowed("view")) {
            $this->_helper->json($returnValueContainer->getData());
        }

        $this->_helper->json(false);
    }

    public function saveAction()
    {
        try {
            if ($this->getParam("id")) {
                $folder = Document\Folder::getById($this->getParam("id"));
                $folder->setModificationDate(time());
                $folder->setUserModification($this->getUser()->getId());

                if ($folder->isAllowed("publish")) {
                    $this->setValuesToDocument($folder);
                    $folder->save();

                    $this->_helper->json(["success" => true]);
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if ($e instanceof Element\ValidationException) {
                $this->_helper->json(["success" => false, "type" => "ValidationException", "message" => $e->getMessage(), "stack" => $e->getTraceAsString(), "code" => $e->getCode()]);
            }
            throw $e;
        }

        $this->_helper->json(false);
    }

    /**
     * @param Document\Folder $folder
     */
    protected function setValuesToDocument(Document\Folder $folder)
    {
        $this->addPropertiesToDocument($folder);
    }
}
