<?php
declare(strict_types=1);

namespace Pimcore\Model\Dto;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetMetaData;

class Asset
{
    public function __construct(
        private int $id,
        private int $parentId,
        private string $type,
        private string $key,
        private string $path,
        private string $fullPath,
        private ?string $mimeType,
        private ?int $fileSize,
        private int $userOwner,
        private ?int $userModification,
        private ?string $locked,
        private bool $isLocked,
        /** @var AssetMetaData[] */
        private array $metaData,
        private ?int $creationDate,
        private ?int $modificationDate,
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Asset
    {
        $this->id = $id;
        return $this;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): Asset
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Asset
    {
        $this->type = $type;
        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): Asset
    {
        $this->key = $key;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): Asset
    {
        $this->path = $path;
        return $this;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function setFullPath(string $fullPath): Asset
    {
        $this->fullPath = $fullPath;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): Asset
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): Asset
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getUserOwner(): int
    {
        return $this->userOwner;
    }

    public function setUserOwner(int $userOwner): Asset
    {
        $this->userOwner = $userOwner;
        return $this;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    public function setUserModification(?int $userModification): Asset
    {
        $this->userModification = $userModification;
        return $this;
    }

    public function getLocked(): ?string
    {
        return $this->locked;
    }

    public function setLocked(?string $locked): Asset
    {
        $this->locked = $locked;
        return $this;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(bool $isLocked): Asset
    {
        $this->isLocked = $isLocked;
        return $this;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function setMetaData(array $metaData): Asset
    {
        $this->metaData = $metaData;
        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(?int $creationDate): Asset
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(?int $modificationDate): Asset
    {
        $this->modificationDate = $modificationDate;
        return $this;
    }

}
