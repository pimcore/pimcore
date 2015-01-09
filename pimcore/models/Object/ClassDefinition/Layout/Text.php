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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Layout;

use Pimcore\Model;

class Text extends Model\Object\ClassDefinition\Layout {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "text";
    
    public $html = "";
    
    /**
     * @return string
     */
    public function getHtml() {
        return $this->html;
    }

    /**
     * @param $html
     * @return $this
     */
    public function setHtml ($html) {
        $this->html = $html;
        return $this;
    }
}
