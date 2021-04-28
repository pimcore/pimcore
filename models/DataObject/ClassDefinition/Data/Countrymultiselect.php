<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\CountryOptionsProvider;

class Countrymultiselect extends Model\DataObject\ClassDefinition\Data\Multiselect
{
    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'countrymultiselect';

    /**
     * Restrict selection to comma-separated list of countries.
     *
     * @internal
     *
     * @var string|null
     */
    public $restrictTo = null;

    /**
     * @param array|string|null $restrictTo
     */
    public function setRestrictTo($restrictTo)
    {
        /**
         * @extjs6
         */
        if (is_array($restrictTo)) {
            $restrictTo = implode(',', $restrictTo);
        }

        $this->restrictTo = $restrictTo;
    }

    /**
     * @return string|null
     */
    public function getRestrictTo()
    {
        return $this->restrictTo;
    }

    /**
     * @return string
     */
    public function getOptionsProviderClass()
    {
        return '@' . CountryOptionsProvider::class;
    }
}
