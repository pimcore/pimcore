<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
        } elseif ($element instanceof Object\Concrete) {
            return $service->getObjectConcreteById($element->getId());
        } elseif ($element instanceof Asset\Folder) {
            return $service->getAssetFolderById($element->getId());
        } elseif ($element instanceof Asset) {
            return $service->getAssetFileById($element->getId());
        } elseif ($element instanceof Document\Folder) {
            return $service->getDocumentFolderById($element->getId());
        } elseif ($element instanceof Document\Snippet) {
            return $service->getDocumentSnippetById($element->getId());
        } elseif ($element instanceof Document\Page) {
            return $service->getDocumentPageById($element->getId());
        } elseif ($element instanceof Document\Link) {
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
