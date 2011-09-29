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

class Document_Hardlink_Wrapper_Hardlink extends Document_Hardlink implements Document_Hardlink_Wrapper_Interface {

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
