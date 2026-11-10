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

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao getDao()
 * @method void delete()
 */
final class KeyGroupRelation extends Model\AbstractModel
{
    private const OPTIONS = 'options';

    /**
     * The closed list of what a definitionOverride may carry: the per group option subset and the
     * unit of a quantity value. Everything else is refused, `type` and `fieldtype` above all - an
     * assignment never changes what a key stores.
     */
    private const OVERRIDABLE_PROPERTIES = [
        self::OPTIONS,
        'defaultUnit',
        'validUnits',
    ];

    protected int $keyId;

    protected int $groupId;

    /**
     * The key
     */
    protected string $name;

    /**
     * The key description.
     */
    protected string $description = '';

    /**
     * Field definition
     */
    protected string $definition;

    /**
     * Field type
     */
    protected string $type;

    protected int $sorter = 0;

    /**
     * The group name
     */
    protected string $groupName;

    protected bool $mandatory = false;

    protected bool $enabled = true;

    /**
     * Carried, not interpreted - no core path reads it.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $metadata = null;

    /**
     * Whether `metadata` carries a value that was loaded or explicitly set.
     *
     * @internal
     */
    protected bool $metadataInitialized = false;

    /**
     * A partial field definition merged over the key's own by {@see self::getEffectiveDefinition()}.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $definitionOverride = null;

    /**
     * Whether `definitionOverride` carries a value that was loaded or explicitly set.
     *
     * @internal
     */
    protected bool $definitionOverrideInitialized = false;

    /**
     * The identifier of the sub group this assignment belongs to, null for none.
     */
    protected ?string $subGroup = null;

    /**
     * Whether `subGroup` carries a value that was loaded or explicitly set.
     *
     * @internal
     */
    protected bool $subGroupInitialized = false;

    public static function create(): self
    {
        return new self();
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function setGroupId(int $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function setKeyId(int $keyId): void
    {
        $this->keyId = $keyId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }

    public function setDefinition(string $definition): void
    {
        $this->definition = $definition;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getSorter(): int
    {
        return $this->sorter;
    }

    public function setSorter(int $sorter): void
    {
        $this->sorter = $sorter;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory): void
    {
        $this->mandatory = $mandatory;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
        $this->metadataInitialized = true;
    }

    /**
     * @internal
     */
    public function isMetadataInitialized(): bool
    {
        return $this->metadataInitialized;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDefinitionOverride(): ?array
    {
        return $this->definitionOverride;
    }

    /**
     * @param array<string, mixed>|null $definitionOverride
     *
     * @throws InvalidConfigException
     */
    public function setDefinitionOverride(?array $definitionOverride): void
    {
        foreach (array_keys($definitionOverride ?? []) as $property) {
            if (!in_array($property, self::OVERRIDABLE_PROPERTIES, true)) {
                throw new InvalidConfigException(
                    sprintf('"%s" cannot be overridden per key assignment.', $property)
                );
            }
        }

        // an emptied unit list would be refilled with every unit wherever the definition is built in an
        // admin request (AbstractQuantityValue::configureOptions()), so it cannot mean "no unit" here
        if (array_key_exists('validUnits', $definitionOverride ?? []) && empty($definitionOverride['validUnits'])) {
            throw new InvalidConfigException('"validUnits" cannot be overridden with an empty list.');
        }

        $this->definitionOverride = $definitionOverride;
        $this->definitionOverrideInitialized = true;
    }

    /**
     * @internal
     */
    public function isDefinitionOverrideInitialized(): bool
    {
        return $this->definitionOverrideInitialized;
    }

    public function getSubGroup(): ?string
    {
        return $this->subGroup;
    }

    public function setSubGroup(?string $subGroup): void
    {
        $this->subGroup = $subGroup;
        $this->subGroupInitialized = true;
    }

    /**
     * @internal
     */
    public function isSubGroupInitialized(): bool
    {
        return $this->subGroupInitialized;
    }

    /**
     * The definition this assignment effectively uses: the one {@see self::getDefinition()} returns
     * with `definitionOverride` merged over it, as the same JSON string. Without a stored override
     * it is that definition unchanged. The editor, the validation and the stores build the key's
     * field definition from it, through {@see KeyGroupRelationCache} and {@see Service}.
     */
    public function getEffectiveDefinition(): string
    {
        $override = $this->definitionOverride;
        if ($override === null) {
            return $this->getDefinition();
        }

        $definition = json_decode($this->getDefinition(), true);

        return json_encode(self::mergeOverride($definition, $override));
    }

    /**
     * Whether the subset of this assignment speaks for a list a provider fills: the key carries no
     * option list of its own, so the list the group narrows is the one the provider hands over.
     *
     * @internal
     */
    public function narrowsProvidedOptions(): bool
    {
        if (!array_key_exists(self::OPTIONS, $this->definitionOverride ?? [])) {
            return false;
        }

        $definition = json_decode($this->getDefinition(), true);
        while (isset($definition['delegateDatatype']) && is_array($definition['delegate'] ?? null)) {
            $definition = $definition['delegate'];
        }

        if (($definition[self::OPTIONS] ?? []) !== []) {
            return false;
        }

        // an empty list of its own, configured on purpose, is a list and not a gap for a provider to
        // fill: the same reading as OptionsProviderInterface::useConfiguredOptions()
        $type = $definition['optionsProviderType'] ?? null;

        return $type !== OptionsProviderInterface::TYPE_CONFIGURE
            && !($type === null && empty($definition['optionsProviderClass'] ?? null));
    }

    /**
     * The effective definition as an array, carrying no options where the subset is for a list a
     * provider fills, so the subset is applied to that list rather than to the empty one it merged into.
     *
     * @return array<string, mixed>
     *
     * @internal
     */
    public function getEffectiveDefinitionData(): array
    {
        $definition = json_decode($this->getEffectiveDefinition(), true);

        if ($this->narrowsProvidedOptions()) {
            // the options live on the inner type of a wrapper, where the override was merged
            $target = &$definition;
            while (isset($target['delegateDatatype']) && is_array($target['delegate'] ?? null)) {
                $target = &$target['delegate'];
            }
            unset($target[self::OPTIONS]);
            unset($target);
        }

        return $definition;
    }

    /**
     * Merges the override into the definition that answers for the VALUE: a wrapper (`multiValue`,
     * `encryptedField`) carries its inner type under `delegate`, and that is where the options and
     * the units live, so the merge goes down to it rather than into the wrapper.
     *
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private static function mergeOverride(array $definition, array $override): array
    {
        if (isset($definition['delegateDatatype']) && is_array($definition['delegate'] ?? null)) {
            $definition['delegate'] = self::mergeOverride($definition['delegate'], $override);

            return $definition;
        }

        foreach ($override as $property => $value) {
            $definition[$property] = $property === self::OPTIONS
                ? self::filterOptions($definition[self::OPTIONS] ?? null, $value)
                : $value;
        }

        if (array_key_exists(self::OPTIONS, $override) && array_key_exists('defaultValue', $definition)) {
            $definition['defaultValue'] = self::reconcileDefaultValue(
                $definition['defaultValue'],
                $definition[self::OPTIONS]
            );
        }

        return $definition;
    }

    /**
     * Drops what a narrowed option list no longer contains from a default value, so a group never
     * offers a default that its own subset excludes.
     *
     * @param array<int, array<string, mixed>>|null $options
     *
     * @internal
     */
    public static function reconcileDefaultValue(mixed $defaultValue, ?array $options): mixed
    {
        if ($defaultValue === null || $defaultValue === '' || $defaultValue === []) {
            return $defaultValue;
        }

        $values = array_map(static fn (array $option) => $option['value'] ?? null, $options ?? []);

        if (is_array($defaultValue)) {
            // an entry may be option shaped (SelectOptionsProviderInterface::getDefaultValue()), in which
            // case it is kept or dropped by its value, and kept in its own shape
            return array_values(array_filter(
                $defaultValue,
                static fn (mixed $v) => in_array(is_array($v) ? ($v['value'] ?? null) : $v, $values, true)
            )) ?: null;
        }

        return in_array($defaultValue, $values, true) ? $defaultValue : null;
    }

    /**
     * The options a provider filled in, narrowed to this assignment's option subset - unchanged
     * without one.
     *
     * @param array<int, array<string, mixed>> $options
     *
     * @return array<int, array<string, mixed>>
     *
     * @internal
     */
    public function applyOptionSubset(array $options): array
    {
        return self::filterOptions($options, $this->definitionOverride[self::OPTIONS] ?? null) ?? $options;
    }

    /**
     * Saves the key assignment, refusing a sub group that names no entry of the vocabulary of the
     * store its group belongs to, and storing the identifier of the entry it resolved to rather than
     * the spelling that was passed.
     *
     * @throws InvalidConfigException
     */
    public function save(): void
    {
        $this->getDao()->save();
    }

    public static function getByGroupAndKeyId(int $groupId, int $keyId): ?KeyGroupRelation
    {
        try {
            $relation = new self();
            $relation->getDao()->getById($keyId, $groupId);

            return $relation;
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Filter rather than replace: one key holds the full option list and each group uses a subset
     * of it, so the key's own label/value pairs are kept in the key's order. A null subset means
     * every option, an empty one means none. Values are compared strictly.
     *
     * @param array<int, array<string, mixed>>|null $options
     * @param array<int, mixed>|null $subset
     *
     * @return array<int, array<string, mixed>>|null
     */
    private static function filterOptions(?array $options, ?array $subset): ?array
    {
        if ($subset === null) {
            return $options;
        }

        // null options are the absence of a list to filter, not an instruction to keep every option
        if ($options === null) {
            return [];
        }

        // the subset speaks the vocabulary of the key's own options, so a pair stands for its value
        $values = array_map(
            static fn (mixed $entry) => is_array($entry) ? ($entry['value'] ?? null) : $entry,
            $subset
        );

        return array_values(array_filter(
            $options,
            static fn (array $option) => in_array($option['value'] ?? null, $values, true)
        ));
    }
}
