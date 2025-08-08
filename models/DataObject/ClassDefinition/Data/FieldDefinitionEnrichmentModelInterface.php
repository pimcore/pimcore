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

use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * See FieldDefinitionEnrichmentModelTrait for implementation/examples
 */
interface FieldDefinitionEnrichmentModelInterface
{
    /**
     * Set values for $context array (if any) and call enrichFieldDefinition on $fieldDefinition.
     */
    public function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data;

    /**
     * Add Data $data to the fieldDefinition collection
     *
     * @return $this
     */
    public function addFieldDefinition(string $key, Data $data): static;

    /**
     * Get Data $data from collection if available
     */
    public function getFieldDefinition(string $key, array $context = []): ?Data;

    /**
     * Get all available fieldDefinitions
     *
     * @return array<string, Data>
     */
    public function getFieldDefinitions(array $context = []): array;

    /**
     * Set fieldDefinition collection
     *
     * @param array<string, Data>|null $fieldDefinitions
     *
     * @return $this
     */
    public function setFieldDefinitions(?array $fieldDefinitions): static;
}
