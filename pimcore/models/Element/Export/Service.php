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
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Element_Export_Service
{

    /**
     * @var Webservice_Service
     */
    protected $webService;



    public function __construct()
    {
        $this->webService = new Webservice_Service();
    }


    /**
     * @param  Element_Interface $element
     * @return void
     */
    public function getApiElement($element)
    {

        $service = new Webservice_Service();
        if ($element instanceof Object_Folder) {
            return $service->getObjectFolderById($element->getId());
        } else if ($element instanceof Object_Concrete) {
            return $service->getObjectConcreteById($element->getId());
        } else if ($element instanceof Asset_Folder) {
            return $service->getAssetFolderById($element->getId());
        } else if ($element instanceof Asset) {
            return $service->getAssetFileById($element->getId());
        } else if ($element instanceof Document_Folder) {
            return $service->getDocumentFolderById($element->getId());
        } else if ($element instanceof Document_Snippet) {
            return $service->getDocumentSnippetById($element->getId());
        } else if ($element instanceof Document_Page) {
            return $service->getDocumentPageById($element->getId());
        } else if ($element instanceof Document_Link) {
            return $service->getDocumentLinkById($element->getId());
        }

    }

    public function extractRelations($element, $apiElementKeys, $recursive, $includeRelations)
    {
        $foundRelations = array();


        if ($includeRelations) {
            $dependency = $element->getDependencies();
            if ($dependency) {

                foreach ($dependency->getRequires() as $r) {
                    if ($e = Element_Service::getDependedElement($r)) {
                        if ($element->getId() != $e->getId() and !in_array(Element_Service::getElementType($e) . "_" . $e->getId(), $apiElementKeys)) {
                            $foundRelations[Element_Service::getElementType($e) . "_" . $e->getId()] = array("elementType" => Element_Service::getType($e),"element" => $e->getId(), "recursive" => false);
                        }

                    }
                }
            }
        }


        $childs = $element->getChilds();
        if ($recursive and $childs) {
            foreach ($childs as $child) {
                if (!in_array(Element_Service::getType($child) . "_" . $child->getId(), $apiElementKeys)) {
                    $foundRelations[Element_Service::getType($child) . "_" . $child->getId()] = array("elementType" => Element_Service::getType($child),"element" => $child->getId(), "recursive" => $recursive);
                }
            }
        }

        return $foundRelations;

    }





}