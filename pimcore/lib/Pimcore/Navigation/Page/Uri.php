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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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
     * @var Document
     */
    protected $_document;


    /**
     * @param  $tabindex
     * @return void
     */
    public function setTabindex($tabindex)
    {
        $this->_tabindex = $tabindex;
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
    public function setAccesskey($accesskey)
    {
        $this->_accesskey = $accesskey;
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
        $this->_document = $document;
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        return $this->_document;
    }
}