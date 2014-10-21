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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Data\KeyValue;

use Pimcore\Model;

class Entry {
    /**
     * @var
     */
    private $value;

    /**
     * @var
     */
    private $translated;

    /**
     * @var
     */
    private $metadata;

    /**
     * @param $value
     * @param $translated
     * @param $metadata
     */
    public function __construct($value, $translated, $metadata) {
        $this->value = $value;
        $this->translated = $translated;
        $this->metadata = $metadata;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getTranslated() {
        return $this->translated;
    }

    /**
     * @return mixed
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return mixed
     */
    public function __toString() {
        return $this->translated !== null ? $this->translated : $this->value;
    }
}
