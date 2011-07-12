<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license dsf sdaf asdf asdf
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Admin_ImportController extends Pimcore_Controller_Action_Admin
{


    public function uploadAction()
    {

        $sid = $this->_getParam("pimcore_admin_sid");

        $tmp_name = $_FILES["Filedata"]["tmp_name"];
        $archive = PIMCORE_TEMPORARY_DIRECTORY . "/import_" . $sid . ".zip";
        $extractDir = PIMCORE_TEMPORARY_DIRECTORY . "/import_" . $sid;
        if (is_file($archive)) {
            unlink($archive);
        }
        if (is_dir($extractDir)) {
            recursiveDelete($extractDir);
        }
        mkdir($extractDir, 0755, true);
        $success = move_uploaded_file($tmp_name, $archive);

        if (!$success) {
            throw new Exception("Could not move uploaded file");
        }

        $zip = new ZipArchive;
        if ($zip->open($archive) === TRUE) {
            $zip->extractTo($extractDir);
            $zip->close();
        }


        $this->_helper->json(array(
                                  "success" => true
                             ));

    }

    public function getUploadInfoAction()
    {
        $extractDir = PIMCORE_TEMPORARY_DIRECTORY . "/import_" . Zend_Session::getId();

        $jobs = array();
        $files = array();
        $dh = opendir($extractDir);
        while ($file = readdir($dh)) {
            if ($file != '.' && $file != '..') {
                $fileNameParts = explode("_", $file);
                $files[$fileNameParts[0]] = $file;
            }
        }
        closedir($dh);

        ksort($files);

        foreach ($files as $file) {
            $jobs[] = array("file" => $file, "task" => "create");
        }
        foreach ($files as $file) {
            $jobs[] = array("file" => $file, "task" => "resolveRelations");
        }
        foreach ($files as $file) {
            $jobs[] = array("file" => $file, "task" => "update");
        }
        $jobs[] = array("file" => null, "task" => "cleanup");

        $this->_helper->json(array(
                                  "success" => true,
                                  "jobs" => $jobs
                             ));

    }


    public function doImportJobsAction()
    {

        $importSession = new Zend_Session_Namespace("element_import");
        if (!$importSession->elementCounter) {
            $importSession->elementCounter = 0;
        }
        if (!$importSession->idMapping) {
            $importSession->idMapping = array();
        }

        $this->removeViewRenderer();

        $importDir = PIMCORE_TEMPORARY_DIRECTORY . "/import_" . Zend_Session::getId();
        $file = $this->_getParam("file");
        $task = $this->_getParam("task");

        $parentId = $this->_getParam("parentId");
        $type = $this->_getParam("type");
        $overwrite = $this->_getParam("overwrite");
        if ($overwrite == 1) {
            $overwrite = true;
        } else {
            $overwrite = false;
        }


        $importService = new Element_Import_Service($this->getUser());

        if ($type == "document") {
            $rootElement = Document::getById($parentId);
        } else if ($type == "object") {
            $rootElement = Object_Abstract::getById($parentId);
        } else if ($type == "asset") {
            $rootElement = Asset::getById($parentId);
        }

        if (!$rootElement) {
            throw new Exception("Invalid root element for import");
        }

        $importData = file_get_contents($importDir . "/" . $file);
        $apiData = unserialize($importData);


        //first run - just save elements so that they are there
        if ($task == "create") {

            $apiElement = $apiData["element"];
            $path = $apiData["path"];

            $element = $importService->create($rootElement, $file, $path, $apiElement, $overwrite, $importSession->elementCounter);

            //set actual ID
            //store id mapping
            $importSession->idMapping[Element_Service::getType($element)][$apiElement->id] = $element->getId();

            $importSession->elementCounter++;

            $importFile = $importDir . "/" . $file;
            file_put_contents($importFile, serialize($apiData));
            chmod($importFile, 0766);
        }


            //second run - all elements have been created, now replace ids
        else if ($task == "resolveRelations") {


            $apiElement = $apiData["element"];

            $type = $this->findElementType($apiElement);

            $importService->correctElementIdRelations($apiElement, $type, $importSession->idMapping);

            //correct relations
            if ($apiElement instanceof Webservice_Data_Object_Concrete) {
                $importService->correctObjectRelations($apiElement, $importSession->idMapping);

            } else if ($apiElement instanceof Webservice_Data_Document_PageSnippet) {
                $importService->correctDocumentRelations($apiElement, $importSession->idMapping);

            } else if ($apiElement instanceof Webservice_Data_Document_Link and $apiElement->internal) {
                $apiElement->target = $importSession->idMapping[$apiElement->internalType][$apiElement->target];
            }

            $importFile = $importDir . "/" . $file;
            file_put_contents($importFile, serialize($apiData));
            chmod($importFile, 0766);
        }


            //third run - set data and relations
        else if ($task == "update") {
            $apiElement = $apiData["element"];

            try {
                $this->updateImportElement($apiElement, $importService);
            } catch (Exception $e) {

                $type = $this->findElementType($apiElement);
                $parent = Element_Service::getElementById($type, $apiElement->parentId);
                $apiElement->key = $this->getImportCopyName($parent->getFullPath(), $apiElement->key, $apiElement->id,$type);
                //try again with different key
                $this->updateImportElement($apiElement, $importService);

            }
        }


        else if ($task == "cleanup") {
            recursiveDelete($importDir);
        }

        $this->_helper->json(array(
                                  "success" => true
                             ));

        //p_r($importService->getImportInfo());


    }

    /**
     * @param  Webservice_Data $apiElement
     * @return string
     */
    protected function findElementType($apiElement){
        if ($apiElement instanceof Webservice_Data_Asset) {
                $type = "asset";
            } else if ($apiElement instanceof Webservice_Data_Object) {
                $type = "object";
            } else if ($apiElement instanceof Webservice_Data_Document) {
                $type = "document";
            }
        return $type;
    }

    protected function updateImportElement($apiElement, $importService)
    {
        if ($apiElement instanceof Webservice_Data_Asset_File) {
            $importService->getWebService()->updateAssetFile($apiElement);
        } else if ($apiElement instanceof Webservice_Data_Asset_Folder) {
            $importService->getWebService()->updateAssetFolder($apiElement);
        } else if ($apiElement instanceof Webservice_Data_Object_Folder) {
            $importService->getWebService()->updateObjectFolder($apiElement);
        } else if ($apiElement instanceof Webservice_Data_Object_Concrete) {
            $importService->getWebService()->updateObjectConcrete($apiElement);
        } else if ($apiElement instanceof Webservice_Data_Document_Folder) {
            $importService->getWebService()->updateDocumentFolder($apiElement);
        } else if ($apiElement instanceof Webservice_Data_Document_Page) {
            $importService->getWebService()->updateDocumentPage($apiElement);
        } else if ($apiElement instanceof Webservice_Data_Document_Snippet) {
            $importService->getWebService()->updateDocumentSnippet($apiElement);
        } else if ($apiElement instanceof Webservice_Data_Document_Link) {
            $importService->getWebService()->updateDocumentLink($apiElement);
        } else {
            throw new Exception("Unknown import element in third run");
        }
    }

    protected function getImportCopyName($intendedPath, $key, $objectId,$type)
    {

        $equalObject = Element_Service::getElementByPath($type,str_replace("//","/",$intendedPath . "/" . $key));
        while ($equalObject and $equalObject->getId() != $objectId) {
            $key .= "_importcopy";
            $equalObject = Element_Service::getElementByPath($type,str_replace("//","/",$intendedPath . "/" . $key));
            if (!$equalObject) {
                break;
            }
        }
        return $key;
    }

    public function importAction()
    {

        $importService = new Element_Import_Service($this->getUser());
        $this->removeViewRenderer();

        $parentId = $this->_getParam("parentId");
        $type = $this->_getParam("type");
        $overwrite = $this->_getParam("overwrite");


        if ($type == "document") {
            $rootElement = Document::getById($parentId);
        } else if ($type == "object") {
            $rootElement = Object_Abstract::getById($parentId);
        } else if ($type == "asset") {
            $rootElement = Asset::getById($parentId);
        }

        if (!$rootElement) {
            throw new Exception("Invalid root element for import");
        }

        $exportName = "export_" . Zend_Session::getId();
        $exportDir = PIMCORE_TEMPORARY_DIRECTORY . "/" . $exportName;
        $exportArchive = $exportDir . ".zip";

        $tmpDirectory = PIMCORE_TEMPORARY_DIRECTORY . "/element_import_" . Zend_Session::getId();

        if (is_dir($tmpDirectory)) {
            recursiveDelete($tmpDirectory);
        }


        $zip = new ZipArchive;
        if ($zip->open($exportArchive) === TRUE) {
            $zip->extractTo($tmpDirectory);
            $zip->close();
        }

        $importService->doImport($tmpDirectory, $rootElement, $overwrite);

        p_r($importService->getImportInfo());

        // cleanup
        recursiveDelete($tmpDirectory);

    }


}