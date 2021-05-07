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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;

class Gender extends Model\DataObject\ClassDefinition\Data\Select
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'gender';

    /**
     * Gender constructor.
     */
    public function __construct()
    {
        $options = [
            ['key' => 'male', 'value' => 'male'],
            ['key' => 'female', 'value' => 'female'],
            ['key' => 'other', 'value' => 'other'],
            ['key' => '', 'value' => 'unknown'],
        ];

        $this->setOptions($options);
    }
}
