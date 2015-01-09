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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Element\Export;

use Pimcore\Model;
use Pimcore\Model\Webservice;
use Pimcore\Model\Element;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;
use Pimcore\Model\Document;

class Service
{

    /**
     * @var Webservice\Service
     */
    protected $webService;



    public function __construct()
    {
        $this->webService = new Webservice\Service();
    }


    /**
     * @param  Element\ElementInterface $element
     * @return void
     */
    public function getApiElement($element)
    {
        $service = new Webservice\Service();
        if ($element instanceof Object\Folder) {
            return $service->getObjectFolderById($element->getId());
        } else if ($element instanceof Object\Concrete) {
            return $service->getObjectConcreteById($element->getId());
        } else if ($element instanceof Asset\Folder) {
            return $service->getAssetFolderById($element->getId());
        } else if ($element instanceof Asset) {
            return $service->getAssetFileById($element->getId());
        } else if ($element instanceof Document\Folder) {
            return $service->getDocumentFolderById($element->getId());
        } else if ($element instanceof Document\Snippet) {
            return $service->getDocumentSnippetById($element->getId());
        } else if ($element instanceof Document\Page) {
            return $service->getDocumentPageById($element->getId());
        } else if ($element instanceof Document\Link) {
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
                    if ($e = Element\Service::getDependedElement($r)) {
                        if ($element->getId() != $e->getId() and !in_array(Element\Service::getElementType($e) . "_" . $e->getId(), $apiElementKeys)) {
                            $foundRelations[Element\Service::getElementType($e) . "_" . $e->getId()] = array("elementType" => Element\Service::getType($e),"element" => $e->getId(), "recursive" => false);
                        }

                    }
                }
            }
        }


        $childs = $element->getChilds();
        if ($recursive and $childs) {
            foreach ($childs as $child) {
                if (!in_array(Element\Service::getType($child) . "_" . $child->getId(), $apiElementKeys)) {
                    $foundRelations[Element\Service::getType($child) . "_" . $child->getId()] = array("elementType" => Element\Service::getType($child),"element" => $child->getId(), "recursive" => $recursive);
                }
            }
        }

        return $foundRelations;

    }





}