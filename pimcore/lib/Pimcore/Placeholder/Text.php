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

namespace Pimcore\Placeholder;

class Text extends AbstractPlaceholder
{

    /**
     * Returns a value for test replacement
     *
     * @return string
     */
    public function getTestValue()
    {
        return '<span class="testValue">Test text</span>';
    }

    /**
     * Replaces the Placeholder with the passed value
     *
     * @return mixed
     */
    public function getReplacement()
    {
        return $this->getValue();
    }
}