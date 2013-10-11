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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */


class Object_Data_KeyValue_Entry {
    private $value;
    private $translated;
    private $metadata;

    public function __construct($value, $translated, $metadata) {
        $this->value = $value;
        $this->translated = $translated;
        $this->metadata = $metadata;
    }

    public function getValue() {
        return $this->value;
    }

    public function getTranslated() {
        return $this->translated;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }


    public function __toString() {
        return $this->translated !== null ? $this->translated : $this->value;
    }
}
