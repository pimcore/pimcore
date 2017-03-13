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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
