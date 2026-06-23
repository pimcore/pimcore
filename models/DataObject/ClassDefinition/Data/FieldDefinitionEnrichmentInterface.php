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

interface FieldDefinitionEnrichmentInterface
{
    /**
     * If in admin mode this method can be implemented to change the fielddefinition whenever
     * getFieldDefinition() get called on the data type.
     * One example purpose is to populate or change dynamic settings like the options for select and multiselect fields.
     * The context param contains contextual information about the container, the field name, etc ...
     *
     *
     * @return $this
     */
    public function enrichFieldDefinition(array $context = []): static;
}
