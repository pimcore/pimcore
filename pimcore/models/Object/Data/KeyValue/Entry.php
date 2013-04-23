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
 * @package    Object
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */


class Object_Data_KeyValue_Entry {
    private $value;
    private $translated;

    public function __construct($value, $translated) {
        $this->value = $value;
        $this->translated = $translated;
    }

    public function getValue() {
        return $this->value;
    }

    public function getTranslated() {
        return $this->translated;
    }


    public function __toString() {
        return $this->translated !== null ? $this->translated : $this->value;
    }
}
