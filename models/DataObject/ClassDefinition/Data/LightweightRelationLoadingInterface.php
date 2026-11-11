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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;

/**
 * Implemented by relation fields that can describe their related elements without instantiating them.
 *
 * Meant for callers that only need to display a relation (see {@see OptimizedAdminLoadingInterface}) and
 * must not pay for loading every related element with all of its own data.
 */
interface LightweightRelationLoadingInterface
{
    /**
     * Reads the relations of this field for the given container with a few bulk queries and returns a plain
     * description of each related element, in the same order as the regular getter: the order of the field for
     * forward relations, the order in which the referencing relations were stored for reverse relations.
     * Elements that no longer exist are omitted; unpublished elements are omitted when
     * {@see \Pimcore\Model\DataObject::doHideUnpublished()} is active, exactly like the regular getter does.
     *
     * `parentId` is 0 for root elements, like `getParentId()`. `subtype` is the element type as returned by
     * `getType()` (e.g. `object`, `folder`, `variant`, `image`, `page`), `className` the data object class name for
     * objects of type `object` and `variant`, null otherwise.
     *
     * @return list<array{id: int, parentId: int, type: string, subtype: string, className: string|null, fullpath: string, filename: string, published: bool}>
     */
    public function loadRelationSummaries(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): array;
}
