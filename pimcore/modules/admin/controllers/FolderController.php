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
        $folder->idPath = Element\Service::getIdPath($folder);
        $folder->userPermissions = $folder->getUserPermissions();
        $folder->setLocked($folder->isLocked());
        $folder->setParent(null);

        $this->minimizeProperties($folder);

        if ($folder->isAllowed("view")) {
            $this->_helper->json($folder);
        }

        $this->_helper->json(false);
    }

    public function saveAction()
    {
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
        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document\Folder $folder)
    {
        $this->addPropertiesToDocument($folder);
    }
}
