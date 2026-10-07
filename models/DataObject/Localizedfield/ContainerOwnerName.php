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

namespace Pimcore\Model\DataObject\Localizedfield;

/**
 * Owner name of the rows (relations, relation metadata, URL slugs) written for localized fields inside a field
 * collection item or an object brick, e.g. `/objectbrick~attributes/Engine/localizedfield~localizedfield`.
 * It contains the item index or the brick type, so items and bricks of the same container sharing a field name
 * don't read or overwrite each other's rows.
 *
 * @internal
 */
final class ContainerOwnerName
{
    /**
     * e.g. `/objectbrick~attributes/Engine/`
     */
    public static function prefix(array $context): string
    {
        $index = $context['index'] ?? $context['containerKey'] ?? null;

        return '/' . $context['containerType'] . '~' . ($context['fieldname'] ?? null) . '/' . $index . '/';
    }

    /**
     * Prefix filter for the PHP side matching of {@see \Pimcore\Model\DataObject\Concrete::retrieveRelationData()}.
     */
    public static function filter(array $context): string
    {
        return self::prefix($context) . '%';
    }

    /**
     * Pattern for SQL `LIKE`: brick keys and field names may contain `_`, which would otherwise match any character.
     */
    public static function likePattern(array $context): string
    {
        return addcslashes(self::prefix($context), '\\%_') . '%';
    }
}
