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
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Service extends Element_Service {

    /**
     * @var User
     */
    protected $_user;
    /**
     * @var array
     */
    protected $_copyRecursiveIds;

    /**
     * @param  User $user
     * @return void
     */
    public function __construct($user) {
        $this->_user = $user;
    }


     /**
      * static function to render a document outside of a view
      *
      * @static
      * @param Document $document
      * @param array $params
      * @return
      */
     public static function render (Document $document, $params = array()) {

        $view = new Pimcore_View();
        $params["document"] = $document;

        $content = $view->action($document->getAction(), $document->getController(), null, $params);
        return $content;
    }

    /**
     * @param  Document $target
     * @param  Document $source
     * @return Document copied document
     */
    public function copyRecursive($target, $source) {

        // avoid recursion
        if (!$this->_copyRecursiveIds) {
            $this->_copyRecursiveIds = array();
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return;
        }


        if ($source instanceof Document_Page || $source instanceof Document_Snippet) {
            $source->getElements();
        }

        $source->getProperties();

        $new = clone $source;
        $new->id = null;
        $new->setChilds(null);
        $new->setKey(Element_Service::getSaveCopyName("document",$new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setResource(null);
        $new->setLocked(false);
        $new->save();

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        foreach ($source->getChilds() as $child) {
            $this->copyRecursive($new, $child);
        }

        $this->updateChilds($target,$new);

        return $new;
    }

    /**
     * @param  Document $target
     * @param  Document $source
     * @return Document copied document
     */
    public function copyAsChild($target, $source) {

        if ($source instanceof Document_Page || $source instanceof Document_Snippet) {
            $source->getElements();
        }

        $source->getProperties();

        $new = clone $source;
        $new->id = null;
        $new->setChilds(null);
        $new->setKey(Element_Service::getSaveCopyName("document",$new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setResource(null);
        $new->setLocked(false);
        $new->save();

        $this->updateChilds($target,$new);

        return $new;
    }

    /**
     * @param  Document $target
     * @param  Document $source
     * @return
     */
    public function copyContents($target, $source) {

        // check if the type is the same
        if (get_class($source) != get_class($target)) {
            throw new Exception("Source and target have to be the same type");
        }

        if ($source instanceof Document_Page || $source instanceof Document_Snippet) {
            $target->setElements($source->getElements());

            $target->setTemplate($source->getTemplate());
            $target->setAction($source->getAction());
            $target->setController($source->getController());

            if ($source instanceof Document_Page) {
                $target->setTitle($source->getTitle());
                $target->setDescription($source->getDescription());
                $target->setKeywords($source->getKeywords());
            }
        }
        else if ($source instanceof Document_Link) {
            $target->setInternalType($source->getInternalType());
            $target->setInternal($source->getInternal());
            $target->setDirect($source->getDirect());
            $target->setLinktype($source->getLinktype());
            $target->setTarget($source->getTarget());
            $target->setParameters($source->getParameters());
            $target->setAnchor($source->getAnchor());
            $target->setTitle($source->getTitle());
            $target->setAccesskey($source->getAccesskey());
            $target->setRel($source->getRel());
            $target->setTabindex($source->getTabindex());
        }

        $target->setPermissions($source->getPermissions());
        $target->setProperties($source->getProperties());
        $target->save();

        return $target;
    }

    /**
     * @param  Document $document
     * @return void
     */
    public static function gridDocumentData($document) {
        $data = Element_Service::gridElementData($document);

        if ($document instanceof Document_Page) {
            $data["title"] = $document->getTitle();
            $data["description"] = $document->getDescription();
            $data["keywords"] = $document->getKeywords();
        } else {
            $data["title"] = "";
            $data["description"] = "";
            $data["keywords"] = "";
            $data["name"] = "";
        }

        return $data;
    }


    public static function loadAllDocumentFields ( $doc ) {

        if($doc instanceof Document_PageSnippet) {
            foreach($doc->getElements() as $name => $data) {
                if(method_exists($data, "load")) {
                    $data->load();
                }
            }
        }

        return $doc;
    }

}