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
