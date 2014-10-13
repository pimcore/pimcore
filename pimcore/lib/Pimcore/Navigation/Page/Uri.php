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

class Pimcore_Navigation_Page_Uri extends Zend_Navigation_Page_Uri
{

    /**
     * @var string
     */
    protected $_accesskey;

    /**
     * @var string
     */
    protected $_tabindex;

    /**
     * @var string
     */
    protected $_relation;

    /**
     * @var int
     */
    protected $_documentId;


    /**
     * @param  $tabindex
     * @return void
     */
    public function setTabindex($tabindex)
    {
        $this->_tabindex = $tabindex;
        return $this;
    }

    /**
     * @return string
     */
    public function getTabindex()
    {
        return $this->_tabindex;
    }

    /**
     * @param  $accesskey
     * @return void
     */
    public function setAccesskey($character = null)
    {
        $this->_accesskey = $character;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccesskey()
    {
        return $this->_accesskey;
    }

    /**
     * @param  $relation
     * @return void
     */
    public function setRelation($relation)
    {
        $this->_relation = $relation;
        return $this;
    }

    /**
     * @return string
     */
    public function getRelation()
    {
        return $this->_relation;
    }

    /**
     * @param Document $document
     */
    public function setDocument($document)
    {
        if($document instanceof Document_Hardlink_Wrapper_Interface) {
            $this->setDocumentId($document->getHardlinkSource()->getId());
        } else if($document instanceof Document) {
            $this->setDocumentId($document->getId());
        }
        return $this;
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        $docId = $this->getDocumentId();
        if($docId) {
            $doc = Document::getById($docId);
            if($doc instanceof Document_Hardlink) {
                $doc = Document_Hardlink_Service::wrap($doc);
            }
            return $doc;
        }

        return null;
    }

    /**
     * @return int
     */
    public function getDocumentId()
    {
        return $this->_documentId;
    }

    /**
     * @param int $documentId
     */
    public function setDocumentId($documentId)
    {
        $this->_documentId = $documentId;
    }
}