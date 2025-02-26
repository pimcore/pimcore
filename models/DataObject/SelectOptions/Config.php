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

namespace Pimcore\Model\DataObject\SelectOptions;

use Exception;
use InvalidArgumentException;
use JsonSerializable;
use Pimcore;
use Pimcore\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use Pimcore\Cache\RuntimeCache;
use Pimcore\DataObject\ClassBuilder\PHPSelectOptionsEnumDumperInterface;
use Pimcore\Helper\ReservedWordsHelper;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Traits\LocateFileTrait;
use Pimcore\Model\Exception\NotFoundException;
use RuntimeException;

/**
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method void delete()
 * @method Config\Dao getDao()
 */
final class Config extends AbstractModel implements JsonSerializable
{
    use LocateFileTrait;

    public const PROPERTY_ID = 'id';

    public const PROPERTY_GROUP = 'group';

    public const PROPERTY_USE_TRAITS = 'useTraits';

    public const PROPERTY_IMPLEMENTS_INTERFACES = 'implementsInterfaces';

    public const PROPERTY_SELECT_OPTIONS = 'selectOptions';

    protected string $id;

    protected ?string $group = null;

    protected string $useTraits = '';

    protected string $implementsInterfaces = '';

    /**
     * @var Data\SelectOption[]
     */
    protected array $selectOptions = [];

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(string $id): static
    {
        $reservedWordsHelper = new ReservedWordsHelper();
        if ($reservedWordsHelper->isReservedWord($id)) {
            throw new InvalidArgumentException(
                'ID must not be one of reserved words: ' . implode(', ', $reservedWordsHelper->getAllReservedWords()),
                1677241981466
            );
        }

        $this->id = $id;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @return $this
     */
    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function hasGroup(): bool
    {
        return !empty($this->group);
    }

    public function getUseTraits(): string
    {
        return $this->useTraits;
    }

    /**
     * @return $this
     */
    public function setUseTraits(string $useTraits): static
    {
        $this->useTraits = $useTraits;

        return $this;
    }

    public function getImplementsInterfaces(): string
    {
        return $this->implementsInterfaces;
    }

    /**
     * @return $this
     */
    public function setImplementsInterfaces(string $implementsInterfaces): static
    {
        $this->implementsInterfaces = $implementsInterfaces;

        return $this;
    }

    /**
     * @return Data\SelectOption[]
     */
    public function getSelectOptions(): array
    {
        return $this->selectOptions;
    }

    /**
     * @return array<string, string>[]
     */
    public function getSelectOptionsAsData(): array
    {
        return array_map(
            fn (Data\SelectOption $selectOption) => $selectOption->toArray(),
            $this->getSelectOptions()
        );
    }

    /**
     * @return $this
     */
    public function setSelectOptions(Data\SelectOption ...$selectOptions): static
    {
        $this->selectOptions = $selectOptions;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSelectOptionsFromData(array $selectOptionsData): static
    {
        $selectOptions = [];
        foreach ($selectOptionsData as $selectOptionData) {
            $selectOptions[] = Data\SelectOption::createFromData($selectOptionData);
        }

        return $this->setSelectOptions(...$selectOptions);
    }

    public function hasSelectOptions(): bool
    {
        return !empty($this->selectOptions);
    }

    public static function getById(string $id): ?Config
    {
        $cacheKey = self::getCacheKey($id);

        try {
            $selectOptions = RuntimeCache::get($cacheKey);
            if (!$selectOptions instanceof self) {
                throw new RuntimeException('Select options in registry is invalid', 1678353750987);
            }
        } catch (Exception $e) {
            try {
                $selectOptions = new self();
                /** @var Config\Dao $dao */
                $dao = $selectOptions->getDao();
                $dao->getById($id);
                RuntimeCache::set($cacheKey, $selectOptions);
            } catch (NotFoundException $e) {
                return null;
            }
        }

        return $selectOptions;
    }

    protected static function getCacheKey(string $key): string
    {
        return 'selectoptions_' . $key;
    }

    public static function createFromData(array $data): static
    {
        // Check whether ID is available
        $id = $data[static::PROPERTY_ID] ?? null;
        if (empty($id)) {
            throw new RuntimeException('ID is mandatory for select options definition', 1676646778230);
        }

        $group = $data[static::PROPERTY_GROUP] ?? null;
        $useTraits = $data[static::PROPERTY_USE_TRAITS] ?? '';
        $implementsInterfaces = $data[static::PROPERTY_IMPLEMENTS_INTERFACES] ?? '';
        $selectOptionsData = $data[static::PROPERTY_SELECT_OPTIONS] ?? [];

        return (new static())
            ->setId($id)
            ->setGroup($group)
            ->setUseTraits($useTraits)
            ->setImplementsInterfaces($implementsInterfaces)
            ->setSelectOptionsFromData($selectOptionsData);
    }

    public function save(): void
    {
        $this->getDao()->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            static::PROPERTY_ID => $this->getId(),
            static::PROPERTY_GROUP => $this->getGroup(),
            static::PROPERTY_USE_TRAITS => $this->getUseTraits(),
            static::PROPERTY_IMPLEMENTS_INTERFACES => $this->getImplementsInterfaces(),
            static::PROPERTY_SELECT_OPTIONS => $this->getSelectOptions(),
        ];
    }

    /**
     * @return array<string, string[]> Class name as key and field names as value
     */
    public function getFieldsUsedIn(): array
    {
        $definitions = [
            ...(new ClassDefinition\Listing())->load(),
            ...(new Fieldcollection\Definition\Listing())->load(),
            ...(new Objectbrick\Definition\Listing())->load(),
        ];

        $fieldsUsedIn = [];
        foreach ($definitions as $definition) {
            $prefix = match (get_class($definition)) {
                ClassDefinition::class => 'Class',
                Fieldcollection\Definition::class => 'Field Collection',
                Objectbrick\Definition::class => 'Objectbrick',
                default => 'Unknown',
            };

            $fieldsUsedIn = [
                ...$fieldsUsedIn,
                ...$this->getFieldsUsedInClass($definition, $prefix),
            ];
        }

        // Add classification store select fields
        foreach ((new Classificationstore\KeyConfig\Listing())->load() as $keyConfiguration) {
            $fieldDefinition = Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfiguration);
            if ($this->isConfiguredAsOptionsProvider($fieldDefinition)) {
                $fieldsUsedIn['Classification Store ' . $keyConfiguration->getStoreId()][] = $fieldDefinition->getName();
            }
        }

        return $fieldsUsedIn;
    }

    /**
     * @return array<string, string[]>
     */
    protected function getFieldsUsedInClass(
        ClassDefinition|Fieldcollection\Definition|Objectbrick\Definition $definition,
        string $prefix
    ): array {
        if (method_exists($definition, 'getName')) {
            $definitionName = $definition->getName();
        } else {
            $definitionName = $definition->getKey();
        }

        $fieldsUsedIn = [];
        foreach ($this->getAllFieldDefinitions($definition) as $fieldDefinition) {
            if ($this->isConfiguredAsOptionsProvider($fieldDefinition)) {
                $fieldsUsedIn[$prefix . ' ' . $definitionName][] = $fieldDefinition->getName();
            }
        }

        return $fieldsUsedIn;
    }

    protected function isConfiguredAsOptionsProvider(?ClassDefinition\Data $fieldDefinition): bool
    {
        if (
            $fieldDefinition === null
            || !$fieldDefinition instanceof ClassDefinition\Data\OptionsProviderInterface
            || empty($fieldDefinition->getOptionsProviderType())
            || $fieldDefinition->getOptionsProviderType() === ClassDefinition\Data\OptionsProviderInterface::TYPE_CONFIGURE
        ) {
            return false;
        }

        $configuredClass = trim($fieldDefinition->getOptionsProviderClass() ?? '', '\\');
        $configuredEnumName = trim($fieldDefinition->getOptionsProviderData() ?? '', '\\ ');

        $expectedEnumNames = [
            $this->getEnumName(),
            $this->getEnumName(true),
        ];

        return $configuredClass === SelectOptionsOptionsProvider::class
            && in_array($configuredEnumName, $expectedEnumNames, true);
    }

    /**
     * @return ClassDefinition\Data[]
     */
    protected function getAllFieldDefinitions(
        ClassDefinition|Fieldcollection\Definition|Objectbrick\Definition $definition
    ): array {
        $fieldDefinitions = $definition->getFieldDefinitions(['suppressEnrichment' => true]);
        $localizedFieldDefinition = $definition->getFieldDefinition('localizedfields', ['suppressEnrichment' => true]);
        if ($localizedFieldDefinition instanceof ClassDefinition\Data\Localizedfields) {
            $fieldDefinitions = [
                ...$fieldDefinitions,
                ...$localizedFieldDefinition->getFieldDefinitions(['suppressEnrichment' => true]),
            ];
        }

        return $fieldDefinitions;
    }

    /**
     * @throws Exception if configured interfaces or traits don't exist
     *
     * @internal
     */
    public function generateEnumFiles(): void
    {
        Pimcore::getContainer()->get(PHPSelectOptionsEnumDumperInterface::class)->dumpPHPEnum($this);
    }

    /**
     * @internal
     */
    public function getPhpClassFile(): string
    {
        return $this->locateFile(ucfirst($this->getId()), 'DataObject/SelectOptions/%s.php');
    }

    public function getEnumName(bool $prependNamespace = false): string
    {
        $className = $this->getId();
        if (!$prependNamespace) {
            return $className;
        }

        return $this->getNamespace() . '\\' . $className;
    }

    public function getNamespace(): string
    {
        return 'Pimcore\\Model\\DataObject\\SelectOptions';
    }
}
