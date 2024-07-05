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

namespace Pimcore\Model\DataObject;

use Exception;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\FieldDefinitionEnrichmentModelInterface;
use Pimcore\Model\DataObject\ClassDefinition\Helper\VarExportInterface;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;
use Pimcore\Model\ModelInterface;

interface ClassDefinitionInterface extends FieldDefinitionEnrichmentModelInterface, ModelInterface, VarExportInterface
{
    public static function getById(string $id, bool $force = false): ?ClassDefinitionInterface;

    public static function getByName(string $name): ?ClassDefinitionInterface;

    /**
     * @param array<string, mixed> $values
     */
    public static function create(array $values = []): ClassDefinitionInterface;

    /**
     * @internal
     */
    public function rename(string $name): void;

    /**
     * @internal
     */
    public static function cleanupForExport(mixed &$data): void;

    /**
     * @throws Exception
     * @throws DefinitionWriteException
     */
    public function save(bool $saveDefinitionFile = true): void;

    /**
     * @internal
     */
    public function generateClassFiles(bool $generateDefinitionFile = true): void;

    /**
     * @throws DefinitionWriteException
     */
    public function delete(): void;

    /**
     * @internal
     */
    public function isWritable(): bool;

    /**
     * @internal
     */
    public function getDefinitionFile(?string $name = null): string;

    /**
     * @internal
     */
    public function getPhpClassFile(): string;

    /**
     * @internal
     */
    public function getPhpListingClassFile(): string;

    public function getId(): ?string;

    public function getName(): ?string;

    public function getCreationDate(): ?int;

    public function getModificationDate(): ?int;

    public function getUserOwner(): ?int;

    public function getUserModification(): ?int;

    /**
     * @return $this
     */
    public function setId(string $id): static;

    /**
     * @return $this
     */
    public function setName(string $name): static;

    /**
     * @return $this
     */
    public function setCreationDate(?int $creationDate): static;

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static;

    /**
     * @return $this
     */
    public function setUserOwner(?int $userOwner): static;

    /**
     * @return $this
     */
    public function setUserModification(?int $userModification): static;

    /**
     * @internal
     */
    public function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data;

    public function getLayoutDefinitions(): ?ClassDefinition\Layout;

    /**
     * @return $this
     */
    public function setLayoutDefinitions(?ClassDefinition\Layout $layoutDefinitions): static;

    public function getParentClass(): string;

    public function getListingParentClass(): string;

    public function getUseTraits(): string;

    /**
     * @return $this
     */
    public function setUseTraits(string $useTraits): static;

    public function getListingUseTraits(): string;

    /**
     * @return $this
     */
    public function setListingUseTraits(string $listingUseTraits): static;

    public function getAllowInherit(): bool;

    public function getAllowVariants(): bool;

    /**
     * @return $this
     */
    public function setParentClass(string $parentClass): static;

    /**
     * @return $this
     */
    public function setListingParentClass(string $listingParentClass): static;

    public function getEncryption(): bool;

    /**
     * @return $this
     */
    public function setEncryption(bool $encryption): static;

    /**
     * @internal
     *
     * @param string[] $tables
     */
    public function addEncryptedTables(array $tables): void;

    /**
     * @internal
     *
     * @param string[] $tables
     */
    public function removeEncryptedTables(array $tables): void;

    /**
     * @internal
     */
    public function isEncryptedTable(string $table): bool;

    public function hasEncryptedTables(): bool;

    /**
     * @internal
     *
     * @return $this
     */
    public function setEncryptedTables(array $encryptedTables): static;

    /**
     * @return $this
     */
    public function setAllowInherit(bool $allowInherit): static;

    /**
     * @return $this
     */
    public function setAllowVariants(bool $allowVariants): static;

    public function getIcon(): ?string;

    /**
     * @return $this
     */
    public function setIcon(?string $icon): static;

    /**
     * @return array<string, array<string, bool>>
     */
    public function getPropertyVisibility(): array;

    /**
     * @param array<string, array<string, bool>> $propertyVisibility
     *
     * @return $this
     */
    public function setPropertyVisibility(array $propertyVisibility): static;

    public function getGroup(): ?string;

    /**
     * @return $this
     */
    public function setGroup(?string $group): static;

    /**
     * @return $this
     */
    public function setDescription(string $description): static;

    public function getDescription(): string;

    /**
     * @return $this
     */
    public function setTitle(string $title): static;

    public function getTitle(): string;

    /**
     * @return $this
     */
    public function setShowVariants(bool $showVariants): static;

    public function getShowVariants(): bool;

    public function getShowAppLoggerTab(): bool;

    /**
     * @return $this
     */
    public function setShowAppLoggerTab(bool $showAppLoggerTab): static;

    public function getShowFieldLookup(): bool;

    /**
     * @return $this
     */
    public function setShowFieldLookup(bool $showFieldLookup): static;

    public function getLinkGeneratorReference(): ?string;

    /**
     * @return $this
     */
    public function setLinkGeneratorReference(?string $linkGeneratorReference): static;

    public function getLinkGenerator(): ?ClassDefinition\LinkGeneratorInterface;

    public function getPreviewGeneratorReference(): ?string;

    public function setPreviewGeneratorReference(?string $previewGeneratorReference): void;

    public function getPreviewGenerator(): ?ClassDefinition\PreviewGeneratorInterface;

    public function isEnableGridLocking(): bool;

    public function setEnableGridLocking(bool $enableGridLocking): void;

    public function getImplementsInterfaces(): ?string;

    /**
     * @return $this
     */
    public function setImplementsInterfaces(?string $implementsInterfaces): static;

    public function getCompositeIndices(): array;

    /**
     * @return $this
     */
    public function setCompositeIndices(array $compositeIndices): static;

    /**
     * @return ClassDefinition\Data[]
     */
    public function getDeletedDataComponents(): array;

    /**
     * @param ClassDefinition\Data[] $deletedDataComponents
     */
    public function setDeletedDataComponents(array $deletedDataComponents): ClassDefinitionInterface;

    public static function getByIdIgnoreCase(string $id): ClassDefinitionInterface|null;
}
