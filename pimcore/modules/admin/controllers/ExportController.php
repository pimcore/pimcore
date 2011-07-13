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

class Admin_ExportController extends Pimcore_Controller_Action_Admin
{


    public function doExportJobsAction()
    {


        $exportSession = new Zend_Session_Namespace("element_export");
        $exportName = "export_" . Zend_Session::getId();
        $exportDir = PIMCORE_WEBSITE_PATH . "/var/tmp/" . $exportName;

        if (!$exportSession->elements) {

            $exportSession->type = $this->_getParam("type");
            $exportSession->id = $this->_getParam("id");
            $exportSession->includeRelations = (bool)$this->_getParam("includeRelations");
            $exportSession->recursive = (bool)$this->_getParam("recursive");
            $exportSession->counter = 0;

            $element = Element_Service::getElementById($exportSession->type, $exportSession->id);

            $exportSession->rootPath = $element->getPath();


            $exportSession->rootType = Element_Service::getType($element);

            $exportSession->elements = array(Element_Service::getType($element) . "_" . $element->getId() => array("elementType"=>Element_Service::getType($element),"element" => $element->getId(), "recursive" => $exportSession->recursive));
            $exportSession->apiElements = array();

            if (is_dir($exportDir)) {
                recursiveDelete($exportDir);
            }
            mkdir($exportDir, 0755, true);

            $this->_helper->json(array("more"=>true, "totalElementsDone"=>0, "totalElementsFound"=>0));

        } else {

            $data = array_pop($exportSession->elements);
            $element = Element_Service::getElementById($data["elementType"],$data["element"]);
            $recursive = $data["recursive"];

            $exportService = new Element_Export_Service();
            $apiElement = $exportService->getApiElement($element);

            $exportSession->foundRelations = $exportService->extractRelations($element, array_keys($exportSession->apiElements), $recursive, $exportSession->includeRelations);

            //make path relative to root
            if (Element_Service::getType($element) == $exportSession->rootType and $exportSession->rootPath == $element->getPath()) {
                $apiElement->path = "";
            } else if (Element_Service::getType($element) == $exportSession->rootType and strpos($element->getPath(), $exportSession->rootPath) === 0) {
                if($exportSession->rootPath === "/"){
                    $len = 1;
                } else {
                    $len = strlen($exportSession->rootPath) - 1;
                }
                $apiElement->path = substr($element->getPath(), $len);
            } else {
                $apiElement->path = $element->getPath();
            }
            $path = $apiElement->path;


            //convert the Webservice _Out element to _In elements
            $outClass = get_class($apiElement);

            $inClass = str_replace("_Out", "_In", $outClass);
            $apiElementIn = new $inClass();
            $objectVars = get_object_vars($apiElementIn);

            foreach ($objectVars as $var => $value) {
                if (property_exists(get_class($apiElement), $var)) {
                    $apiElementIn->$var = $apiElement->$var;
                }
            }
            //remove parentId, add path
            $apiElementIn->parentId = null;

            $apiElement = $apiElementIn;
            $key = Element_Service::getType($element) . "_" . $element->getId();

            $exportSession->apiElements[$key] = array("element" => $apiElement, "path" => $path);
            $exportFile = $exportDir . "/" .$exportSession->counter."_". $key;
            file_put_contents($exportFile, serialize(array("element" => $apiElement, "path" => $path)));
            chmod($exportFile, 0766);

            $exportSession->elements = array_merge($exportSession->elements, $exportSession->foundRelations);



            if(count($exportSession->elements)==0){

                $exportArchive = $exportDir . ".zip";
                if (is_file($exportArchive)) {
                unlink($exportArchive);
            }
            $zip = new ZipArchive();

            $created = $zip->open($exportArchive, ZipArchive::CREATE);
            if ($created === TRUE) {
                $dh = opendir($exportDir);

                while ($file = readdir($dh)) {
                    if ($file != '.' && $file != '..') {
                        $fullFilePath = $exportDir . "/" . $file;
                        if (is_file($fullFilePath)) {
                            $zip->addFile($fullFilePath, str_replace($exportDir . "/", "", $fullFilePath));
                        }
                    }
                }
                closedir($dh);
                $zip->close();
            }
            }
            $exportSession->counter++;
            $this->_helper->json(array("more"=>count($exportSession->elements)!=0, "totalElementsDone"=>count($exportSession->apiElements), "totalElementsFound"=>count($exportSession->foundRelations) ));


        }

    }

    public function getExportFileAction(){

        $exportName = "export_" . Zend_Session::getId();
        $exportDir = PIMCORE_WEBSITE_PATH . "/var/tmp/" . $exportName;
        $exportArchive = $exportDir . ".zip";

        $this->getResponse()->setHeader("Content-Type", "application/zip", true);
        $this->getResponse()->setHeader("Content-Disposition", 'attachment; filename="' . $exportName . '.zip"');

        echo file_get_contents($exportArchive);

        $this->removeViewRenderer();

        //TODO
        //unlink($exportArchive);
    }

    


}