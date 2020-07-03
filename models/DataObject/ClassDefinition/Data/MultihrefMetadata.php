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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

@trigger_error(
    'Data-type `Pimcore\Model\DataObject\ClassDefinition\Data\MultihrefMetadata` is deprecated since version 6.0.0 and will be removed in 7.0.0. ' .
    'Use `' . AdvancedManyToManyRelation::class . '` instead.',
    E_USER_DEPRECATED
);

class_exists(AdvancedManyToManyRelation::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation instead
     */
    class MultihrefMetadata extends AdvancedManyToManyRelation
    {
    }
}
