<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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