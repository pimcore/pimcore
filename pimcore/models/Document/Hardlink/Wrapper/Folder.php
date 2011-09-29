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

class Document_Hardlink_Wrapper_Folder extends Document_Folder implements Document_Hardlink_Wrapper_Interface {


    // OVERWRITTEN METHODS
    public function save() {
        $this->raiseHardlinkError();
    }

    public function update() {
        $this->raiseHardlinkError();
    }

    public function delete() {
        $this->raiseHardlinkError();
    }

    public function getProperties() {

        if($this->properties == null) {

            $directProperties = $this->getResource()->getProperties(false,true);
            $inheritedProperties = $this->getResource()->getProperties(true);
            $hardLinkSourceProperties = array();

            $hardLinkSourcePropertiesTmp = array();
            if($this->getHardLinkSource()->getPropertiesFromSource()) {
                $hardLinkSourceProperties = $this->getHardLinkSource()->getProperties();

                foreach ($hardLinkSourceProperties as $key => $prop) {
                    $prop = clone $prop;
                    if($prop->getInheritable()) {
                        $prop->setInherited(true);
                        $hardLinkSourcePropertiesTmp[$key] = $prop;
                    }
                }
            }

            $properties = array_merge($inheritedProperties, $hardLinkSourcePropertiesTmp, $directProperties);
            $this->setProperties($properties);
        }

        return $this->properties;
    }

    public function getChilds() {

        if ($this->childs === null) {
            $childs = parent::getChilds();

            $hardLink = $this->getHardLinkSource();

            if($hardLink->getChildsFromSource() && $hardLink->getSourceDocument() && !Pimcore::inAdmin()) {
                foreach($childs as &$c) {
                    $c = Document_Hardlink_Wrapper::wrap($c);
                    $c->setHardLinkSource($hardLink);
                    $c->setPath(preg_replace("@^" . preg_quote($hardLink->getSourceDocument()->getFullpath()) . "@", $hardLink->getFullpath(), $c->getPath()));
                }
            }

            $this->setChilds($childs);
        }

        return $this->childs;
    }



    /**
     * @var Document_Hardlink
     */
    protected $hardLinkSource;

    /**
     * @throws Exception
     * @return void
     */
    protected function raiseHardlinkError () {
        throw new Exception("Method no supported by hardlinked documents");
    }

    /**
     * @param \Document_Hardlink $hardLinkSource
     */
    public function setHardLinkSource($hardLinkSource)
    {
        $this->hardLinkSource = $hardLinkSource;
    }

    /**
     * @return \Document_Hardlink
     */
    public function getHardLinkSource()
    {
        return $this->hardLinkSource;
    }
}
