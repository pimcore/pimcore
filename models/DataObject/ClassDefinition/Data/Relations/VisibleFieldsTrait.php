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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Exception;
use Pimcore;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Metadata\Predefined;

/**
 * Adds a "visible fields" feature to relation types that may reference elements of several types
 * (documents, assets, objects of one or more classes).
 *
 * The set of fields that can be shown is the union of the fields available for every allowed
 * element type; a field that does not apply to a related element's type simply has no value for
 * that row. The resolved definitions are exposed through {@see enrichLayoutDefinition()} as
 * `visibleFieldDefinitions`, the per-row values through {@see getVisibleFieldData()}.
 *
 * @internal
 */
trait VisibleFieldsTrait
{
    /**
     * @internal
     *
     * @var string[]|string|null
     */
    public array|string|null $visibleFields = null;

    /**
     * @internal
     *
     * @var array<string, array<string, mixed>>
     */
    public array $visibleFieldDefinitions = [];

    /**
     * @param string[]|string|null $visibleFields
     *
     * @return $this
     */
    public function setVisibleFields(array|string|null $visibleFields): static
    {
        if (is_array($visibleFields)) {
            $visibleFields = implode(',', $visibleFields);
        }
        $this->visibleFields = $visibleFields !== '' ? $visibleFields : null;

        return $this;
    }

    public function getVisibleFields(): array|null|string
    {
        return $this->visibleFields;
    }

    /**
     * @return string[]
     */
    public function getVisibleFieldNames(): array
    {
        $visibleFields = $this->visibleFields;
        if (is_string($visibleFields)) {
            $visibleFields = explode(',', $visibleFields);
        }
        if (!is_array($visibleFields)) {
            return [];
        }

        $names = [];
        foreach ($visibleFields as $name) {
            $name = trim((string) $name);
            if ($name !== '' && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Resolves the configured visible fields against the fields available for the allowed element
     * types and stores the result in `visibleFieldDefinitions` (consumed by the UI layers).
     */
    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $this->visibleFieldDefinitions = [];

        $names = $this->getVisibleFieldNames();
        if (!$names) {
            return $this;
        }

        if (!isset($context['purpose'])) {
            $context['purpose'] = 'layout';
        }

        $available = $this->getAvailableVisibleFields($context);

        foreach ($names as $name) {
            if (isset($available[$name])) {
                $this->visibleFieldDefinitions[$name] = $available[$name];

                continue;
            }

            // unknown field (e.g. class/metadata definition removed in the meantime): keep the
            // configured name so the UI can still render a column, as a plain read-only input
            $this->visibleFieldDefinitions[$name] = VisibleFieldDefinitionHelper::buildFallbackDefinition($name) + ['sources' => []];
        }

        return $this;
    }

    /**
     * Returns the superset of fields that can be selected as visible fields for this definition,
     * derived from the allowed element types: every field of every allowed object class
     * (including localized fields), the system properties and predefined metadata of assets and
     * the system properties of documents.
     *
     * Each entry is keyed by the field name and carries at least `name`, `title`, `fieldtype`,
     * `noteditable` and `sources` (the element types / classes the field originates from, e.g.
     * `object` for properties of every object, `object:Product` for the fields of a class, `asset`,
     * `document`). The first definition encountered for a name wins;
     * further sources are only appended to `sources`.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAvailableVisibleFields(array $context = []): array
    {
        $fields = [];

        if ($this->getObjectsAllowed()) {
            // properties every object has, whether or not the relation is restricted to classes
            $this->mergeVisibleFieldCandidates($fields, $this->getCommonVisibleFieldCandidates(), 'object');

            foreach ($this->getClasses() as $classItem) {
                $class = VisibleFieldDefinitionHelper::resolveClass($classItem['classes']);
                if (!$class) {
                    continue;
                }

                $this->mergeVisibleFieldCandidates(
                    $fields,
                    $this->getObjectVisibleFieldCandidates($class, $context),
                    'object:' . $class->getName()
                );
            }
        }

        if ($this->getAssetsAllowed()) {
            $this->mergeVisibleFieldCandidates($fields, $this->getAssetVisibleFieldCandidates(), 'asset');
        }

        if ($this->getDocumentsAllowed()) {
            $this->mergeVisibleFieldCandidates($fields, $this->getDocumentVisibleFieldCandidates(), 'document');
        }

        return $fields;
    }

    /**
     * Resolves the values of the configured visible fields for one related element. Names that are not
     * offered for this definition (see getAvailableVisibleFields()), or not offered for the element's type
     * or class, resolve to null.
     *
     * @return array<string, mixed>
     */
    public function getVisibleFieldData(Element\ElementInterface $element, array $params = []): array
    {
        $sources = $this->getVisibleFieldSources($params['context'] ?? []);
        $elementSources = $this->getVisibleFieldSourcesOf($element);

        $data = [];
        foreach ($this->getVisibleFieldNames() as $name) {
            $data[$name] = array_intersect($sources[$name] ?? [], $elementSources)
                ? $this->resolveVisibleFieldValue($element, $name, $params)
                : null;
        }

        return $data;
    }

    /**
     * The names getAvailableVisibleFields() offers and where each comes from (the same `sources` as there),
     * without describing the fields: the class fields are read without enrichment, so this is cheap enough to
     * be called per related element. Asset and document names come from the same candidate hooks as the
     * described fields, so a subclass offering additional fields there has them resolved as well.
     *
     * @return array<string, string[]>
     */
    public function getVisibleFieldSources(array $context = []): array
    {
        $sources = [];
        $add = static function (array $names, string $source) use (&$sources): void {
            foreach ($names as $name) {
                if (!in_array($source, $sources[$name] ?? [], true)) {
                    $sources[$name][] = $source;
                }
            }
        };

        if ($this->getObjectsAllowed()) {
            $add($this->getCommonVisibleFieldNames(), 'object');

            foreach ($this->getClasses() as $classItem) {
                $class = VisibleFieldDefinitionHelper::resolveClass($classItem['classes']);
                if ($class) {
                    $add($this->getObjectVisibleFieldNames($class, $context), 'object:' . $class->getName());
                }
            }
        }

        if ($this->getAssetsAllowed()) {
            $add(array_keys($this->getAssetVisibleFieldCandidates()), 'asset');
        }

        if ($this->getDocumentsAllowed()) {
            $add(array_keys($this->getDocumentVisibleFieldCandidates()), 'document');
        }

        return $sources;
    }

    /**
     * The sources of getAvailableVisibleFields() an element's fields can come from.
     *
     * @return string[]
     */
    protected function getVisibleFieldSourcesOf(Element\ElementInterface $element): array
    {
        if ($element instanceof Concrete) {
            return ['object', 'object:' . $element->getClassName()];
        }

        if ($element instanceof AbstractObject) {
            // object folders (and variants of unrestricted relations): the common properties apply
            return ['object'];
        }

        if ($element instanceof Asset) {
            return ['asset'];
        }

        if ($element instanceof Document) {
            return ['document'];
        }

        return [];
    }

    /**
     * @return string[]
     */
    protected function getCommonVisibleFieldNames(): array
    {
        return ['creationDate', 'modificationDate'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getCommonVisibleFieldCandidates(): array
    {
        $candidates = [];
        foreach ($this->getCommonVisibleFieldNames() as $name) {
            $candidates[$name] = $this->buildVisibleFieldCandidate($name, 'date');
        }

        return $candidates;
    }

    /**
     * Names of the top-level and localized data fields of an object class, read without enriching the
     * definitions (see getObjectVisibleFieldCandidates() for the described fields).
     *
     * @return string[]
     */
    protected function getObjectVisibleFieldNames(ClassDefinition $class, array $context = []): array
    {
        $context['suppressEnrichment'] = true;
        $names = [];

        foreach ($class->getFieldDefinitions($context) as $fieldDefinition) {
            if ($fieldDefinition instanceof Data\Localizedfields) {
                foreach ($fieldDefinition->getFieldDefinitions($context) as $localizedFieldDefinition) {
                    if ($this->isVisibleFieldCandidate($localizedFieldDefinition)) {
                        $names[] = $localizedFieldDefinition->getName();
                    }
                }
            } elseif ($this->isVisibleFieldCandidate($fieldDefinition)) {
                $names[] = $fieldDefinition->getName();
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The predefined asset metadata that can be offered for this definition: one definition per name, the
     * first whose target subtype is not excluded by the allowed asset types.
     *
     * @return array<string, Predefined>
     */
    protected function getApplicablePredefinedAssetMetadata(): array
    {
        $allowedSubtypes = array_filter(array_map(
            static fn (array $item): string => $item['assetTypes'],
            $this->getAssetTypes()
        ));

        $applicable = [];
        foreach ($this->getPredefinedAssetMetadataByName() as $name => $definitions) {
            foreach ($definitions as $definition) {
                $targetSubtype = $definition->getTargetSubtype();
                if ($targetSubtype && $allowedSubtypes && !in_array($targetSubtype, $allowedSubtypes, true)) {
                    continue;
                }

                $applicable[$name] = $definition;

                break;
            }
        }

        return $applicable;
    }

    /**
     * Top-level and localized data fields of an object class.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getObjectVisibleFieldCandidates(ClassDefinition $class, array $context = []): array
    {
        $candidates = [];

        foreach ($class->getFieldDefinitions($context) as $fieldDefinition) {
            if ($fieldDefinition instanceof Data\Localizedfields) {
                foreach (array_keys($fieldDefinition->getFieldDefinitions($context)) as $localizedFieldName) {
                    // resolve the child through the class so that it is enriched with the class as context
                    // (the container would pass itself, which e.g. options providers do not expect)
                    $localizedFieldDefinition = VisibleFieldDefinitionHelper::findClassFieldDefinition($class, $localizedFieldName, $context);
                    if ($localizedFieldDefinition && $this->isVisibleFieldCandidate($localizedFieldDefinition)) {
                        $candidate = VisibleFieldDefinitionHelper::buildDefinition($localizedFieldDefinition);
                        $candidate['localized'] = true;
                        $candidates[$localizedFieldName] ??= $candidate;
                    }
                }

                continue;
            }

            if ($this->isVisibleFieldCandidate($fieldDefinition)) {
                $candidates[$fieldDefinition->getName()] ??= VisibleFieldDefinitionHelper::buildDefinition($fieldDefinition);
            }
        }

        return $candidates;
    }

    /**
     * System properties and predefined metadata of assets. Override to offer additional asset fields
     * (e.g. from asset metadata class definitions) and resolve their values in
     * resolveAssetVisibleFieldValue(): the names returned here are the ones getVisibleFieldData()
     * resolves for assets.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getAssetVisibleFieldCandidates(): array
    {
        $candidates = array_merge($this->getCommonVisibleFieldCandidates(), [
            'filename' => $this->buildVisibleFieldCandidate('filename', 'input'),
            'mimetype' => $this->buildVisibleFieldCandidate('mimetype', 'input'),
            'fileSize' => $this->buildVisibleFieldCandidate('fileSize', 'numeric'),
        ]);

        foreach ($this->getApplicablePredefinedAssetMetadata() as $name => $definition) {
            $candidates[$name] ??= $this->buildVisibleFieldCandidateFromPredefinedMetadata($definition);
        }

        return $candidates;
    }

    /**
     * The predefined asset metadata definitions, grouped by name (a name may exist once per language and
     * asset subtype).
     *
     * @return array<string, Predefined[]>
     */
    protected function getPredefinedAssetMetadataByName(): array
    {
        try {
            return Predefined::getAllByName();
        } catch (Exception $e) {
            Logger::debug('Could not load predefined asset metadata for visible fields: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Whether a visible field name is a predefined metadata definition that applies to this asset's subtype;
     * metadata that only exists ad hoc on the asset, or that is defined for another subtype, is not shown.
     */
    protected function isApplicableAssetMetadata(Asset $asset, string $name): bool
    {
        foreach ($this->getPredefinedAssetMetadataByName()[$name] ?? [] as $definition) {
            $targetSubtype = $definition->getTargetSubtype();
            if (!$targetSubtype || $targetSubtype === $asset->getType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * System properties of documents. Override to offer additional document fields and resolve their
     * values in resolveDocumentVisibleFieldValue().
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getDocumentVisibleFieldCandidates(): array
    {
        return $this->getCommonVisibleFieldCandidates();
    }

    protected function resolveVisibleFieldValue(Element\ElementInterface $element, string $name, array $params = []): mixed
    {
        if ($name === 'creationDate') {
            return $element->getCreationDate();
        }
        if ($name === 'modificationDate') {
            return $element->getModificationDate();
        }

        if ($element instanceof Concrete) {
            return $this->resolveObjectVisibleFieldValue($element, $name, $params);
        }

        if ($element instanceof Asset) {
            return $this->resolveAssetVisibleFieldValue($element, $name, $params);
        }

        if ($element instanceof Document) {
            return $this->resolveDocumentVisibleFieldValue($element, $name, $params);
        }

        return null;
    }

    protected function resolveObjectVisibleFieldValue(Concrete $object, string $name, array $params = []): mixed
    {
        $context = $params['context'] ?? [];
        $language = $params['language'] ?? null;

        $fieldDefinition = VisibleFieldDefinitionHelper::findClassFieldDefinition($object->getClass(), $name, $context);
        if (!$fieldDefinition || !$this->isVisibleFieldCandidate($fieldDefinition)) {
            return null;
        }

        try {
            $value = $object->get($name, $language);

            return $fieldDefinition->getDataForEditmode($value, $object, $params);
        } catch (Exception $e) {
            Logger::debug(sprintf('Could not resolve visible field "%s" of object %d: %s', $name, $object->getId(), $e->getMessage()));

            return null;
        }
    }

    protected function resolveAssetVisibleFieldValue(Asset $asset, string $name, array $params = []): mixed
    {
        switch ($name) {
            case 'filename':
                return $asset->getFilename();
            case 'mimetype':
                return $asset->getMimeType();
            case 'fileSize':
                return $asset->getFileSize();
        }

        if (!$this->isApplicableAssetMetadata($asset, $name)) {
            return null;
        }

        $value = $asset->getMetadata($name, $params['language'] ?? null);
        if ($value instanceof Element\ElementInterface) {
            return $value->getRealFullPath();
        }

        return $value;
    }

    protected function resolveDocumentVisibleFieldValue(Document $document, string $name, array $params = []): mixed
    {
        return null;
    }

    /**
     * Fields that can be rendered as a read-only grid column. Container types (field collections,
     * object bricks, blocks, classification stores, nested localized fields) are skipped, as are
     * fields holding secrets (passwords, encrypted fields), which must never be exposed as a column.
     */
    protected function isVisibleFieldCandidate(Data $fieldDefinition): bool
    {
        return !($fieldDefinition instanceof Data\Localizedfields
            || $fieldDefinition instanceof Data\Fieldcollections
            || $fieldDefinition instanceof Data\Objectbricks
            || $fieldDefinition instanceof Data\Block
            || $fieldDefinition instanceof Data\Classificationstore
            || $fieldDefinition instanceof Data\Password
            || $fieldDefinition instanceof Data\EncryptedField);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildVisibleFieldCandidate(string $name, string $fieldtype, ?string $title = null): array
    {
        if ($title === null) {
            $title = Pimcore::getContainer()->get('translator')->trans($name, [], 'admin');
        }

        return [
            'name' => $name,
            'title' => $title,
            'fieldtype' => $fieldtype,
            'noteditable' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildVisibleFieldCandidateFromPredefinedMetadata(Predefined $definition): array
    {
        $metadataType = (string) $definition->getType();
        $fieldtype = match ($metadataType) {
            'input', 'textarea', 'checkbox', 'date', 'select' => $metadataType,
            default => 'input',
        };

        $candidate = $this->buildVisibleFieldCandidate((string) $definition->getName(), $fieldtype);
        $candidate['metadataType'] = $metadataType;
        if ($definition->getLanguage()) {
            $candidate['language'] = $definition->getLanguage();
        }

        if ($metadataType === 'select') {
            $options = [];
            foreach (explode(',', (string) $definition->getConfig()) as $option) {
                $option = trim($option);
                if ($option !== '') {
                    $options[] = ['key' => $option, 'value' => $option];
                }
            }
            $candidate['options'] = $options;
        }

        return $candidate;
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     * @param array<string, array<string, mixed>> $candidates
     */
    private function mergeVisibleFieldCandidates(array &$fields, array $candidates, string $source): void
    {
        foreach ($candidates as $name => $candidate) {
            if (!isset($fields[$name])) {
                $candidate['sources'] = [$source];
                $fields[$name] = $candidate;

                continue;
            }

            $sources = $fields[$name]['sources'];
            if (is_array($sources) && !in_array($source, $sources, true)) {
                $sources[] = $source;
                $fields[$name]['sources'] = $sources;
            }
        }
    }
}
