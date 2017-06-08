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

namespace Pimcore\Model\Object\ClassDefinition\DynamicOptionsProvider;

interface MultiSelectOptionsProviderInterface
{
    /**
     * @param $context array
     * @param $fieldDefinition Data
     *
     * @return array
     */
    public function getOptions($context, $fieldDefinition);

    /**
     * @param $context array
     * @param $fieldDefinition Data
     *
     * @return bool
     */
    public function hasStaticOptions($context, $fieldDefinition);
}
