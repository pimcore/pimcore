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

namespace Pimcore\View\Helper;

class HeadScript extends \Zend_View_Helper_HeadScript {
    /**
     * Retrieve string representation
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {

        // adds the automatic cache buster functionality
        foreach ($this as &$item) {
            if (!$this->_isValid($item)) {
                continue;
            }

            if(is_array($item->attributes)) {
                if(isset($item->attributes["src"])) {
                    $realFile = PIMCORE_DOCUMENT_ROOT . $item->attributes["src"];
                    if(file_exists($realFile)) {
                        $item->attributes["src"] = $item->attributes["src"] . "?_dc=" . filemtime($realFile);
                    }
                }
            }
        }

        return parent::toString($indent);
    }
}