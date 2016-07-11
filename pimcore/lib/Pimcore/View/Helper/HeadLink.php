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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\View\Helper;

class HeadLink extends \Zend_View_Helper_HeadLink
{
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
            if (isset($item->href)) {
                $realFile = PIMCORE_DOCUMENT_ROOT . $item->href;
                if (file_exists($realFile)) {
                    $item->href = "/cache-buster-" . filemtime($realFile) . $item->href;
                }
            }
        }

        return parent::toString($indent);
    }
}
