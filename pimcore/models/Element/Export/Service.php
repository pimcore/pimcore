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
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\Export;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Object;
use Pimcore\Model\Webservice;

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
     *
     * @return mixed
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

    /**
     * @param $element
     * @param $apiElementKeys
     * @param $recursive
     * @param $includeRelations
     *
     * @return array
     */
    public function extractRelations($element, $apiElementKeys, $recursive, $includeRelations)
    {
        $foundRelations = [];

        if ($includeRelations) {
            $dependency = $element->getDependencies();
            if ($dependency) {
                foreach ($dependency->getRequires() as $r) {
                    if ($e = Element\Service::getDependedElement($r)) {
                        if ($element->getId() != $e->getId() and !in_array(Element\Service::getElementType($e) . '_' . $e->getId(), $apiElementKeys)) {
                            $foundRelations[Element\Service::getElementType($e) . '_' . $e->getId()] = ['elementType' => Element\Service::getType($e), 'element' => $e->getId(), 'recursive' => false];
                        }
                    }
                }
            }
        }

        $childs = $element->getChilds();
        if ($recursive and $childs) {
            foreach ($childs as $child) {
                if (!in_array(Element\Service::getType($child) . '_' . $child->getId(), $apiElementKeys)) {
                    $foundRelations[Element\Service::getType($child) . '_' . $child->getId()] = ['elementType' => Element\Service::getType($child), 'element' => $child->getId(), 'recursive' => $recursive];
                }
            }
        }

        return $foundRelations;
    }
}
