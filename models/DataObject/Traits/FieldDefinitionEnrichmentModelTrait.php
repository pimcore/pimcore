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

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
trait FieldDefinitionEnrichmentModelTrait
{
    /**
     * @var Data[]
     */
    protected ?array $fieldDefinitionsCache = null;

    /**
     * @param Data[] $fieldDefinitions
     *
     * @return $this
     */
    public function setFieldDefinitions(?array $fieldDefinitions): static
    {
        $this->fieldDefinitionsCache = $fieldDefinitions;

        return $this;
    }

    protected function doGetFieldDefinitions(mixed $def = null, array $fields = []): array
    {
        if ($def === null) {
            $def = $this->getChildren();
        }

        if (is_array($def)) {
            foreach ($def as $child) {
                $fields = array_merge($fields, $this->doGetFieldDefinitions($child, $fields));
            }
        }

        if ($def instanceof ClassDefinition\Layout) {
            if ($def->hasChildren()) {
                foreach ($def->getChildren() as $child) {
                    $fields = array_merge($fields, $this->doGetFieldDefinitions($child, $fields));
                }
            }
        }

        if ($def instanceof ClassDefinition\Data) {
            $existing = $fields[$def->getName()] ?? false;
            if ($existing && method_exists($existing, 'addReferencedField')) {
                // this is especially for localized fields which get aggregated here into one field definition
                // in the case that there are more than one localized fields in the class definition
                // see also pimcore.object.edit.addToDataFields();
                $existing->addReferencedField($def);
            } else {
                $fields[$def->getName()] = $def;
            }
        }

        return $fields;
    }

    protected function  getFieldDefinitionsForData(array $context = []): void {
        if (empty($this->fieldDefinitionsCache)) {
            $definitions = $this->doGetFieldDefinitions();
            foreach ($this->getReferencedFields() as $rf) {
                if ($rf instanceof ClassDefinition\Data\Localizedfields) {
                    $definitions = array_merge($definitions, $this->doGetFieldDefinitions($rf->getChildren()));
                }
            }

            $this->fieldDefinitionsCache = $definitions;
        }
    }

    /**
     * @return Data[]|null
     */
    public function getFieldDefinitions(array $context = []): ?array
    {
        if($this instanceof ClassDefinition\Data) {
            $this->getFieldDefinitionsForData($context);
        }

        if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
            return $this->fieldDefinitionsCache;
        }

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
     * @return $this
     */
    public function addFieldDefinition(string $key, Data $data): static
    {
        $this->fieldDefinitionsCache[$key] = $data;

        return $this;
    }

    public function getFieldDefinition(string $key, array $context = []): ?Data
    {
        if(!isset($this->fieldDefinitionsCache)) {
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
                if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
                    return $fieldDefinition;
                }

                return $this->doEnrichFieldDefinition($fieldDefinition, $context);
            }
        }

        return null;
    }

    abstract protected function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data;
}
