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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Placeholder_Object extends Pimcore_Placeholder_Abstract
{

    /**
     * Returns a value for test replacement
     *
     * @return string
     */
    public function getTestValue()
    {
        return '<span class="testValue">Name of the Object</span>';
    }

    /**
     * Gets a object by it's id and replaces the placeholder width the value form the called "method"
     *
     * example: %Object(object_id,{"method" : "getId"});
     * @return string
     */
    public function getReplacement()
    {
        $string = '';
        $object = is_object($this->getValue()) ? $this->getValue() : Object_Concrete::getById($this->getValue());

        if ($object) {
            if (is_string($this->getPlaceholderConfig()->method) && method_exists($object, $this->getPlaceholderConfig()->method)) {
                $string = $object->{$this->getPlaceholderConfig()->method}($this->getLocale());
            }
        }
        return $string;
    }
}