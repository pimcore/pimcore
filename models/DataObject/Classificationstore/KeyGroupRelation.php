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

use JsonException;
use Pimcore\Model;
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao getDao()
 * @method void save()
 * @method void delete()
 */
final class KeyGroupRelation extends Model\AbstractModel
{
    private const OPTIONS = 'options';

    private const DEFAULT_UNIT = 'defaultUnit';

    private const VALID_UNITS = 'validUnits';

    /**
     * The closed list of what a definitionOverride may carry: the per group option subset and the
     * unit of a quantity value. Everything else is refused, `type` and `fieldtype` above all - an
     * assignment never changes what a key stores. Each entry describes the value the property
     * takes, for the message of a refusal.
     */
    private const OVERRIDABLE_PROPERTIES = [
        self::OPTIONS => 'a list of option values, or null for every option',
        self::DEFAULT_UNIT => 'a string, or null',
        self::VALID_UNITS => 'a list of unit ids',
    ];

    /**
     * The length of the `subGroup` column.
     */
    private const SUB_GROUP_MAX_LENGTH = 190;

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
     * The sub-group of this assignment, stored verbatim; null means no sub-group.
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
     *
     * @throws InvalidConfigException
     */
    public function setMetadata(?array $metadata): void
    {
        self::assertJsonEncodable($metadata, 'metadata');

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
        foreach ($definitionOverride ?? [] as $property => $value) {
            if (!array_key_exists($property, self::OVERRIDABLE_PROPERTIES)) {
                throw new InvalidConfigException(
                    sprintf('"%s" cannot be overridden per key assignment.', $property)
                );
            }

            if (!self::isAcceptedOverrideValue($property, $value)) {
                throw new InvalidConfigException(sprintf(
                    'The "%s" override takes %s, %s given.',
                    $property,
                    self::OVERRIDABLE_PROPERTIES[$property],
                    get_debug_type($value)
                ));
            }
        }

        self::assertJsonEncodable($definitionOverride, 'definition override');

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

    /**
     * @throws InvalidConfigException
     */
    public function setSubGroup(?string $subGroup): void
    {
        if ($subGroup !== null && mb_strlen($subGroup) > self::SUB_GROUP_MAX_LENGTH) {
            throw new InvalidConfigException(sprintf(
                'A sub-group is at most %d characters, the given one is %d.',
                self::SUB_GROUP_MAX_LENGTH,
                mb_strlen($subGroup)
            ));
        }

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
     * it is that definition unchanged. Nothing in core reads this yet - the editor still renders
     * {@see self::getDefinition()}.
     */
    public function getEffectiveDefinition(): string
    {
        $override = $this->definitionOverride;
        if ($override === null) {
            return $this->getDefinition();
        }

        $definition = json_decode($this->getDefinition(), true);

        foreach ($override as $property => $value) {
            $definition[$property] = $property === self::OPTIONS
                ? self::filterOptions($definition[self::OPTIONS] ?? null, $value)
                : $value;
        }

        return json_encode($definition);
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
     * The value type each overridable property takes, which is the one its consumer accepts:
     * `options` is the subset {@see self::filterOptions()} filters with, and the two unit
     * properties are the arguments of AbstractQuantityValue::setDefaultUnit() and
     * ::setValidUnits(). Without this an override of the wrong type is stored happily and only
     * fails once something reads it back.
     *
     * Only ever reached for a property of {@see self::OVERRIDABLE_PROPERTIES}, so the default arm
     * stands for a property added to that list without an arm here - refusing it is the safe way
     * round, and the test over the full list catches it.
     */
    private static function isAcceptedOverrideValue(string $property, mixed $value): bool
    {
        return match ($property) {
            self::OPTIONS => $value === null || is_array($value),
            self::DEFAULT_UNIT => $value === null || is_string($value),
            self::VALID_UNITS => is_array($value),
            default => false,
        };
    }

    /**
     * Both JSON columns are written with json_encode(), which has no way of reporting a failure
     * other than returning a value the column would then hold instead of the data. Refuse here,
     * where the caller can still do something about it.
     *
     * @param array<string, mixed>|null $value
     *
     * @throws InvalidConfigException
     */
    private static function assertJsonEncodable(?array $value, string $property): void
    {
        if ($value === null) {
            return;
        }

        try {
            json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidConfigException(
                sprintf('The %s cannot be encoded as JSON: %s', $property, $exception->getMessage()),
                previous: $exception
            );
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
        if ($subset === null || $options === null) {
            return $options;
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
