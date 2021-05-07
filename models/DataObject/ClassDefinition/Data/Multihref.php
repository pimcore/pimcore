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

@trigger_error(
    'Data-type `\Pimcore\Model\DataObject\ClassDefinition\Data\Multihref` is deprecated since version 6.0.0 and will be removed in Pimcore 10. ' .
    'Use `' . ManyToManyRelation::class . '` instead.',
    E_USER_DEPRECATED
);

class_exists(ManyToManyRelation::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation instead
     */
    class Multihref extends ManyToManyRelation
    {
    }
}
