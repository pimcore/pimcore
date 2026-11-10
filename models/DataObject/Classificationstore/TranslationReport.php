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
 * What one reference translator rewrote and what it could not.
 *
 * This is a public extension point and therefore intentionally not `@internal`.
 */
final readonly class TranslationReport
{
    /**
     * @param list<UnresolvedReference> $unresolved
     */
    public function __construct(
        public int $translated,
        public array $unresolved = []
    ) {
    }
}
