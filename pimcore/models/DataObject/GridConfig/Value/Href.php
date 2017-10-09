<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\GridConfig\Value;


class Href extends DefaultValue {

    protected $format;

    public function __construct($config, $context = null) {
        parent::__construct($config, $context);

    }

    public function getLabeledValue($object) {

        $result = new \stdClass();
        $result->label = $this->label;


        $getter = "get" . ucfirst($this->attribute);
        if (method_exists($object, $getter)) {
            $result->value = $object->$getter();
            return $result;
        }

        return $result;
    }
}

