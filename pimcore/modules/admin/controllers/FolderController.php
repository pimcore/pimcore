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

class Admin_FolderController extends \Pimcore\Controller\Action\Admin\Document
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

        $folder = Document\Folder::getById($this->getParam("id"));
        $folder = clone $folder;

        $folder->idPath = Element\Service::getIdPath($folder);
        $folder->userPermissions = $folder->getUserPermissions();
        $folder->setLocked($folder->isLocked());
        $folder->setParent(null);

        $this->addTranslationsData($folder);
        $this->minimizeProperties($folder);

        if ($folder->isAllowed("view")) {
            $this->_helper->json($folder);
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

                    $this->_helper->json(array("success" => true));
                }
            }
        } catch (\Exception $e) {
            \Logger::log($e);
            if (Pimcore\Tool\Admin::isExtJS6() && $e instanceof Element\ValidationException) {
                $this->_helper->json(array("success" => false, "type" => "ValidationException", "message" => $e->getMessage(), "stack" => $e->getTraceAsString(), "code" => $e->getCode()));
            }
            throw $e;
        }

        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document\Folder $folder)
    {
        $this->addPropertiesToDocument($folder);
    }
}
