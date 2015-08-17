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

class HeadLink extends \Zend_View_Helper_HeadLink {
    /**
     * Render link elements as string
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {
        // adds the automatic cache buster functionality
        foreach ($this as $item) {
            if(isset($item->href)) {
                $realFile = PIMCORE_DOCUMENT_ROOT . $item->href;
                if(file_exists($realFile)) {
                    $item->href = $item->href . "?_dc=" . filemtime($realFile);
                }
            }
        }

        return parent::toString($indent);
    }

}