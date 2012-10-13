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

class Document_Hardlink_Wrapper_Page extends Document_Page implements Document_Hardlink_Wrapper_Interface {

    // OVERWRITTEN METHODS
    public function save() {
        $this->raiseHardlinkError();
    }

    protected function update() {
        $this->raiseHardlinkError();
    }

    public function delete() {
        $this->raiseHardlinkError();
    }

    public function getProperties() {

        if($this->properties == null) {

            if($this->getHardLinkSource()->getPropertiesFromSource()) {
                $sourceProperties = $this->getResource()->getProperties();
            } else {
                $sourceProperties = array();
            }

            $hardLinkProperties = array();
            $hardLinkSourceProperties = $this->getHardLinkSource()->getProperties();
            foreach ($hardLinkSourceProperties as $key => $prop) {
                $prop = clone $prop;
                $prop->setInherited(true);

                // if the property doesn't exist in the source-properties just add it
                if(!array_key_exists($key, $sourceProperties)) {
                    $hardLinkProperties[$key] = $prop;
                } else {
                    // if the property does exist in the source properties but it is inherited, then overwrite it with the hardlink property
                    if($sourceProperties[$key]->isInherited()) {
                        $hardLinkProperties[$key] = $prop;
                    }
                }
            }


            $properties = array_merge($sourceProperties, $hardLinkProperties);
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
                    $c = Document_Hardlink_Service::wrap($c);
                    $c->setHardLinkSource($hardLink);
                    $c->setPath(preg_replace("@^" . preg_quote($hardLink->getSourceDocument()->getRealFullpath()) . "@", $hardLink->getRealFullpath(), $c->getRealPath()));
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
