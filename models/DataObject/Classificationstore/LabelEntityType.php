<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Classificationstore;

/**
 * The kind of classification store entity a {@see Label} row addresses.
 *
 * SubGroup has no entity in this release. It is part of the enum so that labels imported for
 * sub-groups are stored rather than refused; nothing resolves against it until the entity exists.
 */
enum LabelEntityType: string
{
    case Store = 'store';
    case Collection = 'collection';
    case Group = 'group';
    case SubGroup = 'subgroup';
    case Key = 'key';
}
