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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Exception;
use Pimcore\Model\DataObject\Concrete;

interface LayoutDefinitionEnrichmentInterface
{
    /**
     * Override point for enriching the object's layout definition before the layout is returned to the admin interface.
     * An example would the select datatype with a dynamic options provider.
     *
     *
     * @param array<string, mixed> $context additional contextual data like fieldname etc.
     *
     * @return $this
     *
     * @throws Exception
     */
    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static;
}
