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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Controller_Action_Helper_Json extends Zend_Controller_Action_Helper_Json {

    public function direct($data, $sendNow = true, $keepLayouts = false) {

        $data = $this->filterCycles($data);

        // hack for FCGI because ZF doesn't care of duplicate headers
        $this->getResponse()->clearHeader("Content-Type");

        $this->suppressExit = !$sendNow;

        $d = $this->sendJson($data, $keepLayouts);
        return $d;
    }


    /*
        recursion detection/filter

        this is necessary since json_encode() doesn't handle recursions anymore (PHP 5.5), in later versions it was
        possible to suppress the warning with @json_encode() but not json_encode() returns an empty string without a warning
    */
    private $processedObjects = array();

    protected function filterCycles ($element) {
        if(is_array($element)) {
            foreach ($element as &$value) {
                $value = $this->filterCycles($value);
            }
        } else if (is_object($element)) {

            $clone = clone $element; // do not modify the original object

            if(in_array($element, $this->processedObjects, true)) {
                return '"* RECURSION (' . get_class($element) . ') *"';
            }

            $this->processedObjects[] = $element;

            $propCollection = get_object_vars($clone);

            foreach ($propCollection as $name => $propValue) {
                $clone->$name = $this->filterCycles($propValue);
            }

            array_splice($this->processedObjects, array_search($element, $this->processedObjects, true), 1);

            return $clone;
        }

        return $element;
    }
}
