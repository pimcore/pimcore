<?php

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
