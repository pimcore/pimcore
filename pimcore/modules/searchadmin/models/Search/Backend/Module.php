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

class Search_Backend_Module extends Pimcore_API_Module_Abstract{

    protected function postAddElement(Element_Interface $element){
        $searchEntry = new Search_Backend_Data($element);
        $searchEntry->save();

    }

    protected function preDeleteElement(Element_Interface $element){
        
        $searchEntry = Search_Backend_Data::getForElement($element);
        if($searchEntry instanceof Search_Backend_Data and $searchEntry->getId() instanceof Search_Backend_Data_Id){
            $searchEntry->delete();
        }

    }

    protected function postUpdateElement(Element_Interface $element){
        $searchEntry = Search_Backend_Data::getForElement($element);
        if($searchEntry instanceof Search_Backend_Data and $searchEntry->getId() instanceof Search_Backend_Data_Id ){
            $searchEntry->setDataFromElement($element);
            $searchEntry->save();
        } else {
            $this->postAddElement($element);
        }
    }

    /**
     *
     * Hook called after an asset was added
     *
     * @param Asset $asset
     */
    public function postAddAsset(Asset $asset) {
        $this->postAddElement($asset);
    }


    /**
     * Hook called before an asset is deleted
     *
     * @param Asset $asset
     */
    public function preDeleteAsset(Asset $asset) {
        $this->preDeleteElement($asset);
    }

    /**
     * Hook called after an asset is updated
     *
     * @param Asset $asset
     */
    public function postUpdateAsset(Asset $asset) {
        $this->postUpdateElement($asset);
    }

    /**
     *
     * Hook called after a document was added
     *
     * @param Document $document
     */
    public function postAddDocument(Document $document) {
        $this->postAddElement($document);
    }

    /**
     * Hook called before a document is deleted
     *
     * @param Document $document
     */
    public function preDeleteDocument(Document $document) {
        $this->preDeleteElement($document);
    }

    /**
     * Hook called after  a document is updated
     *
     * @param Document $document
     */
    public function postUpdateDocument(Document $document) {
       $this->postUpdateElement($document);
    }

    /**
     * Hook after an object was is added
     *
     * @param Object_Abstract $object
     */
    public function postAddObject(Object_Abstract $object) {
        $this->postAddElement($object);
    }

    /**
     * Hook called before an object is deleted
     *
     * @param Object_Abstract $object
     */
    public function preDeleteObject(Object_Abstract $object) {
        $this->preDeleteElement($object);
    }

    /**
     * Hook called after an object was updated
     *
     * @param Object_Abstract $object
     */
    public function postUpdateObject(Object_Abstract $object) {
        $this->postUpdateElement($object);
    }


}