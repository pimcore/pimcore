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
 * One reference a translator could not rewrite, with where it was found and why.
 *
 * This is a public extension point and therefore intentionally not `@internal`.
 */
final readonly class UnresolvedReference
{
    public function __construct(
        public string $location,
        public string $reference,
        public string $reason
    ) {
    }
}
