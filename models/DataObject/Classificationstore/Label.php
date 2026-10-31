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
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Model\Exception\NotFoundException;

/**
 * The translated title and description of a classification store entity, addressed by
 * (entityType, entityId, language).
 *
 * Labels are data, not a translation workflow: the language is stored as given, so a label for a
 * locale the installation has not configured yet is kept and simply not reached until it is.
 *
 * @method \Pimcore\Model\DataObject\Classificationstore\Label\Dao getDao()
 * @method void save()
 * @method void delete()
 */
final class Label extends Model\AbstractModel
{
    private const LANGUAGE_MAX_LENGTH = 16;

    protected LabelEntityType $entityType;

    protected int $entityId;

    protected string $language;

    /**
     * The translated title, null when the row only carries a description.
     */
    protected ?string $title = null;

    /**
     * The translated description, null when the row only carries a title.
     */
    protected ?string $description = null;

    public static function create(): self
    {
        return new self();
    }

    public static function getByEntity(LabelEntityType $entityType, int $entityId, string $language): ?self
    {
        try {
            $label = new self();
            $label->getDao()->getByEntity($entityType, $entityId, $language);

            return $label;
        } catch (NotFoundException) {
            return null;
        }
    }

    public function getEntityType(): LabelEntityType
    {
        return $this->entityType;
    }

    public function setEntityType(LabelEntityType $entityType): void
    {
        $this->entityType = $entityType;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): void
    {
        $this->entityId = $entityId;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @throws InvalidConfigException
     */
    public function setLanguage(string $language): void
    {
        // strict SQL mode is off by default, so a key too long for the column would be truncated
        if (strlen($language) > self::LANGUAGE_MAX_LENGTH) {
            throw new InvalidConfigException(
                sprintf('"%s" is longer than a classification store label language may be.', $language)
            );
        }

        $this->language = $language;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
