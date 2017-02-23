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

class HeadMeta extends \Zend_View_Helper_HeadMeta
{
    /**
     * @var array
     */
    protected $rawItems = [];

    /**
     * Determine if item is valid
     *
     * @param  mixed $item
     * @return boolean
     */
    protected function _isValid($item)
    {
        return true;
    }

    /**
     * @param null $indent
     * @return string
     */
    public function toString($indent = null)
    {
        $metaString = parent::toString($indent);

        // add raw items
        $separator = $this->_escape($this->getSeparator()) . $indent;
        $metaString .= ($separator . implode($separator, $this->rawItems));

        return $metaString;
    }

    /**
     * @param string $html
     * @return $this
     */
    public function addRaw($html)
    {
        $this->rawItems[] = $html;

        return $this;
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return $this->rawItems;
    }
}
