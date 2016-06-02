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
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document;

use \Pimcore\Model\Document;

class Printcontainer extends Document\PrintAbstract
{
    /**
     * Static type of the document
     *
     * @var string
     */
    public $type = "printcontainer";


    public function getController()
    {
        if (empty($this->controller)) {
            $this->controller = "container";
        }
        return $this->controller;
    }

    public function getModule()
    {
        if (empty($this->module)) {
            $this->module = "Web2Print";
        }
        return $this->module;
    }

    public function getAction()
    {
        if (empty($this->action)) {
            $this->action = "container";
        }
        return $this->action;
    }


    public function getTreeNodeConfig()
    {
        $tmpDocument = [];
        $tmpDocument["leaf"] = false;
        $tmpDocument["expanded"] = $this->hasNoChilds();
        $tmpDocument["iconCls"] = "pimcore_icon_printcontainer";
        $tmpDocument["permissions"] = [
            "view" => $this->isAllowed("view"),
            "remove" => $this->isAllowed("delete"),
            "settings" => $this->isAllowed("settings"),
            "rename" => $this->isAllowed("rename"),
            "publish" => $this->isAllowed("publish"),
            "create" => $this->isAllowed("create")
        ];

        return $tmpDocument;
    }


    private $allChildren;
    public function getAllChildren()
    {
        $this->allChildren = [];
        $this->doGetChildren($this);
        return $this->allChildren;
    }

    private function doGetChildren(Document $document)
    {
        $children = $document->getChilds();
        foreach ($children as $child) {
            if ($child instanceof Document\Printpage) {
                $this->allChildren[] = $child;
            }

            if ($child instanceof Document\Folder || $child instanceof Document\Printcontainer) {
                $this->doGetChildren($child);
            }

            if ($child instanceof Document\Hardlink) {
                if ($child->getSourceDocument() instanceof Document\Printpage) {
                    $this->allChildren[] = $child;
                }

                $this->doGetChildren($child);
            }
        }
    }


    public function pdfIsDirty()
    {
        $dirty = parent::pdfIsDirty();
        if (!$dirty) {
            $dirty = ($this->getLastGenerated() < $this->getLastedChildMofidicationDate());
        }

        return $dirty;
    }


    public function getCssModificationForPreview($includeChildren = false)
    {
        if ($includeChildren) {
            $allChildren = $this->getAllChildren();

            $modifications = parent::getCssModificationForPreview();
            if (!$modifications) {
                $modifications = [];
            }

            if ($allChildren) {
                foreach ($allChildren as $child) {
                    $workingChild = $child;
                    if ($child instanceof Document\Hardlink) {
                        $workingChild = $child->getSourceDocument();
                    }
                    $childModifications = $workingChild->getCssModificationForPreview(true);
                    if ($childModifications) {
                        $modifications = array_merge($modifications, $childModifications);
                    }
                }
            }

            return $modifications;
        } else {
            return parent::getCssModificationForPreview();
        }
    }
}
