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

class Admin_LinkController extends Pimcore_Controller_Action_Admin_Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->getParam("id"), "document")
            ));
        }
        Element_Editlock::lock($this->getParam("id"), "document");
        $link = Document_Link::getById($this->getParam("id"));
        
        $modificationDate = $link->getModificationDate();
        $link = $this->getLatestVersion($link);
        
        $link->setVersions(array_splice($link->getVersions(), 0, 1));
        $link->getScheduledTasks();
        $link->setObject(null);
        $link->idPath = Element_Service::getIdPath($link);
        $link->userPermissions = $link->getUserPermissions();
        $link->setLocked($link->isLocked());
        $link->setParent(null);

        $this->minimizeProperties($link);

        if ($link->isAllowed("view")) {
            $this->_helper->json($link);
        }

        $this->_helper->json(false);
    }
    
    public function saveAction() {
        if ($this->getParam("id")) {
            $link = Document_Link::getById($this->getParam("id"));
            $link = $this->getLatestVersion($link);
            $this->setValuesToDocument($link);
            
            $link->setModificationDate(time());
            $link->setUserModification($this->getUser()->getId());

            if ($this->getParam("task") == "unpublish") {
                $link->setPublished(false);
            }
            if ($this->getParam("task") == "publish") {
                $link->setPublished(true);
            }


            if (($this->getParam("task") == "publish" && $link->isAllowed("publish")) or ($this->getParam("task") == "unpublish" && $link->isAllowed("unpublish"))) {

                try {
                    $link->save();
                    $this->saveToSession($link);
                    $this->_helper->json(array("success" => true));
                } catch (Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }


            }
            else {
                if ($snippet->isAllowed("save")) {

                    try {
                        $link->saveVersion();
                        $this->saveToSession($link);
                        $this->_helper->json(array("success" => true));
                    } catch (Exception $e) {
                        $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                    }


                }
            }
        }

        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document_Link $link) {

        // data
        $data = Zend_Json::decode($this->getParam("data"));

        if (!empty($data["path"])) {
            if ($document = Document::getByPath($data["path"])) {
                $data["linktype"] = "internal";
                $data["internalType"] = "document";
                $data["internal"] = $document->getId();
            }
            else if ($asset = Asset::getByPath($data["path"])) {
                $data["linktype"] = "internal";
                $data["internalType"] = "asset";
                $data["internal"] = $asset->getId();
            }
            else {
                $data["linktype"] = "direct";
                $data["direct"] = $data["path"];
            }
        }

        unset($data["path"]);

        $link->setValues($data);
        $this->addPropertiesToDocument($link);
        $this->addSchedulerToDocument($link);
    }
    
    public function showVersionAction() {
         $version = Version::getById($this->getParam("id"));
         $versionData = $version->loadData();
         
         echo "<html><head></head><body><span>".htmlspecialchars($versionData->getHref())."</span></html>";
         $this->removeViewRenderer();
    }
    
    public function diffVersionsAction() {
        include_once 'DaisyDiff/HTMLDiff.php';
        include_once 'simple_html_dom.php';

        $versionFrom = Version::getById($this->getParam("from"));
        $versionTo = Version::getById($this->getParam("to"));

        $docFrom = $versionFrom->loadData();
        $docTo = $versionTo->loadData();
        
        $from = str_get_html("<span>".htmlspecialchars($docFrom->getHref())."</span>");
        $to = str_get_html("<span>".htmlspecialchars($docTo->getHref())."</span>");
        
        $diff = new HTMLDiffer();
        $text = $diff->htmlDiff($from, $to);

        echo "<html><head>"
        .'<link rel="stylesheet" type="text/css" href="/pimcore/static/css/daisydiff.css" />'
        . "</head><body>$text</body></html>";

        $this->removeViewRenderer();
    }

}
