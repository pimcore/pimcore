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
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider;

use Pimcore\Model\DataObject\ClassDefinition\Data;

interface SelectOptionsProviderInterface extends MultiSelectOptionsProviderInterface
{
    /**
     * @param array $context
     * @param Data $fieldDefinition
     *
     * @return string|null
     */
    public function getDefaultValue($context, $fieldDefinition);
}
