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
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Element_AdminStyle {

    protected $elementCssClass;
    protected $elementIcon;
    protected $elementIconClass;


    public function __construct($element) {
        if($element->getType() == "folder") {
            $this->elementIconClass = "pimcore_icon_folder";
        } else {
            if($element->getClass()->getIcon()) {
                $this->elementIcon = $element->getClass()->getIcon();
            } else {
                $this->elementIconClass = "pimcore_icon_object";
            }
        }
    }

    public function setElementCssClass($elementCssClass) {
        $this->elementCssClass = $elementCssClass;
    }

    public function getElementCssClass() {
        return $this->elementCssClass;
    }

    public function setElementIcon($elementIcon) {
        $this->elementIcon = $elementIcon;
    }

    public function getElementIcon() {
        return $this->elementIcon;
    }

    public function setElementIconClass($elementIconClass) {
        $this->elementIconClass = $elementIconClass;
    }

    public function getElementIconClass() {
        return $this->elementIconClass;
    }


}
