<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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