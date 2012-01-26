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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Layout_Panel extends Object_Class_Layout {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "panel";


    /**
     * Width of input field labels
     * @var int
     */
    public $labelWidth = 100;

    /**
     * @var string
     */
    public $layout;


    /**
     * @param int $labelWidth
     */
    public function setLabelWidth($labelWidth)
    {
        if(!empty($labelWidth)) {
            $this->labelWidth = intval($labelWidth);
        }
    }

    /**
     * @return int
     */
    public function getLabelWidth()
    {
        return $this->labelWidth;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }
}
