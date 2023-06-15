<?php
declare(strict_types=1);

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

namespace Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider;

use Pimcore\Model\DataObject\ClassDefinition\Data;

interface MultiSelectOptionsProviderInterface
{
    public function getOptions(array $context, Data $fieldDefinition): array;

    /**
     * Whether options are depending on the object context (i.e. different options for different objects) or not.
     * This is especially important for exposing options in the object grid. For options depending on object-context
     * there will be no batch assignment mode, and filtering can only be done through a text field instead of the
     * options list.
     *
     *
     */
    public function hasStaticOptions(array $context, Data $fieldDefinition): bool;
}
