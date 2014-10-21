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

class Button extends Model\Object\ClassDefinition\Layout {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "button";

    /**
     * @var
     */
    public $handler;

    /**
     * @var
     */
    public $text;

    /**
     * @return mixed
     */
    public function getText() {
        return $this->text;
    }

    /**
     * @param $text
     * @return $this
     */
    public function setText($text) {
        $this->text = $text;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHandler() {
        return $this->handler;
    }

    /**
     * @param $handler
     * @return $this
     */
    public function setHandler($handler) {
        $this->handler = $handler;
        return $this;
    }
}
