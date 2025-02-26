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

namespace Pimcore\Model\DataObject\Traits;

use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
trait FieldDefinitionEnrichmentModelTrait
{
    /**
     * @var array<string, Data>|null
     */
    protected ?array $fieldDefinitionsCache = null;

    /**
     * @param array<string, Data>|null $fieldDefinitions
     *
     * @return $this
     */
    public function setFieldDefinitions(?array $fieldDefinitions): static
    {
        $this->fieldDefinitionsCache = $fieldDefinitions;

        return $this;
    }

    public function suppressEnrichment(array $context): bool
    {
        return !Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment']);
    }

    /**
     * @return array<string, Data>
     */
    protected function getEnrichedFieldDefinitions(array $context = []): array
    {
        $enrichedFieldDefinitions = [];
        if (is_array($this->fieldDefinitionsCache)) {
            foreach ($this->fieldDefinitionsCache as $key => $fieldDefinition) {
                $fieldDefinition = $this->doEnrichFieldDefinition($fieldDefinition, $context);
                $enrichedFieldDefinitions[$key] = $fieldDefinition;
            }
        }

        return $enrichedFieldDefinitions;
    }

    /**
     * @return array<string, Data>
     */
    public function getFieldDefinitions(array $context = []): array
    {
        if ($this->suppressEnrichment($context)) {
            return $this->fieldDefinitionsCache ?? [];
        }

        return $this->getEnrichedFieldDefinitions($context);
    }

    /**
     * @return $this
     */
    public function addFieldDefinition(string $key, Data $data): static
    {
        $this->fieldDefinitionsCache[$key] = $data;

        return $this;
    }

    public function getFieldDefinition(string $key, array $context = []): ?Data
    {
        if (!isset($this->fieldDefinitionsCache)) {
            $this->getFieldDefinitions($context);
        }

        if (isset($this->fieldDefinitionsCache)) {
            $fieldDefinition = null;

            if (array_key_exists($key, $this->fieldDefinitionsCache)) {
                $fieldDefinition = $this->fieldDefinitionsCache[$key];
            } elseif (array_key_exists('localizedfields', $this->fieldDefinitionsCache)) {
                $localizedFields = $this->fieldDefinitionsCache['localizedfields'];
                if ($localizedFields instanceof ClassDefinition\Data\Localizedfields) {
                    $fieldDefinition = $localizedFields->getFieldDefinition($key);
                }
            }

            if ($fieldDefinition) {
                if ($this->suppressEnrichment($context)) {
                    return $fieldDefinition;
                }

                return $this->doEnrichFieldDefinition($fieldDefinition, $context);
            }
        }

        return null;
    }

    abstract protected function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data;
}
