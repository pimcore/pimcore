<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

class AdminStyle {

    protected $elementCssClass;
    protected $elementIcon;
    protected $elementIconClass;
    protected $elementQtipConfig;

    public function __construct($element) {
        if($element->getType() == "folder") {
            $this->elementIconClass = "pimcore_icon_folder";
            $this->elementQtipConfig = array(
                "title" => "ID: " . $element->getId()
            );
        } else {
            if($element->getClass()->getIcon()) {
                $this->elementIcon = $element->getClass()->getIcon();
            } else {
                $this->elementIconClass = "pimcore_icon_object";
            }

            $this->elementQtipConfig = array(
                "title" => "ID: " . $element->getId(),
                "text" => 'Type: ' . $element->getClass()->getName()
            );
        }
    }

    public function setElementCssClass($elementCssClass) {
        $this->elementCssClass = $elementCssClass;
        return $this;
    }

    public function getElementCssClass() {
        return $this->elementCssClass;
    }

    public function setElementIcon($elementIcon) {
        $this->elementIcon = $elementIcon;
        return $this;
    }

    public function getElementIcon() {
        return $this->elementIcon;
    }

    public function setElementIconClass($elementIconClass) {
        $this->elementIconClass = $elementIconClass;
        return $this;
    }

    public function getElementIconClass() {
        return $this->elementIconClass;
    }

    public function getElementQtipConfig() {
        return $this->elementQtipConfig;
    }

    public function setElementQtipConfig($elementQtipConfig) {
        $this->elementQtipConfig = $elementQtipConfig;
    }


}
