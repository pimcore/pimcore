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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Loader;

class ClassMapAutoloader extends \Zend_Loader_ClassMapAutoloader {

    public function autoload($class) {

        parent::autoload($class);

        // compatibility layer
        if(!class_exists($class, false) && !interface_exists($class, false) && preg_match("/^(Pimcore)_/", $class)) {

            $class = "\\" . ltrim($class, "\\"); // ensure that the class is in the global namespace
            $namespacedClass = $class;

            // different abstract naming
            if(preg_match("/_Abstract$/", $namespacedClass)) {
                $namespacedClass = preg_replace("/([^_]+)_Abstract$/", "$1_Abstract$1", $namespacedClass);
            }

            if(preg_match("/_Interface$/", $namespacedClass)) {
                $namespacedClass = preg_replace("/([^_]+)_Interface$/", "$1_$1Interface", $namespacedClass);
            }

            $namespacedClass = str_replace("_", "\\", $namespacedClass);
            if(class_exists($namespacedClass)) {
                class_alias($namespacedClass, $class);
            }
        }
    }
}