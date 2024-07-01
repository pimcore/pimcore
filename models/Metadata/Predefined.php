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

namespace Pimcore\Model\Metadata;

use Exception;
use Pimcore;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Logger;
use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Metadata\Predefined\Dao getDao()
 * @method void save()
 * @method void delete()
 * @method bool isWriteable()
 * @method string getWriteTarget()
 */
final class Predefined extends Model\AbstractModel
{
    protected ?string $id = null;

    protected ?string $name = null;

    protected ?string $description = null;

    protected ?string $type = null;

    protected ?string $targetSubtype = null;

    protected mixed $data = null;

    protected ?string $config = null;

    protected ?string $language = null;

    protected ?string $group = null;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    public static function getById(string $id): ?Predefined
    {
        try {
            $metadata = new self();
            $metadata->getDao()->getById($id);

            return $metadata;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public static function getByName(string $name, string $language = ''): ?Predefined
    {
        try {
            $metadata = new self();
            $metadata->setName($name);
            $metadata->getDao()->getByNameAndLanguage($name, $language);

            return $metadata;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function create(): Predefined
    {
        $type = new self();

        return $type;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = str_replace('~', '---', $name);

        return $this;
    }

    /**
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return $this
     */
    public function setData(mixed $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return $this
     */
    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @return $this
     */
    public function setLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @return $this
     */
    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @return $this
     */
    public function setTargetSubtype(?string $targetSubtype): static
    {
        $this->targetSubtype = $targetSubtype;

        return $this;
    }

    public function getTargetSubtype(): ?string
    {
        return $this->targetSubtype;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    /**
     * @return $this
     */
    public function setConfig(?string $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function minimize(): void
    {
        try {
            $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            /** @var Model\Asset\MetaData\ClassDefinition\Data\Data $instance */
            $instance = $loader->build($this->type);
            $this->data = $instance->getDataFromEditMode($this->data);
        } catch (UnsupportedException $e) {
            Logger::error('could not resolve asset metadata implementation for ' . $this->type);
        }
    }

    public function expand(): void
    {
        try {
            $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            /** @var Model\Asset\MetaData\ClassDefinition\Data\Data $instance */
            $instance = $loader->build($this->type);
            $this->data = $instance->getDataForEditmode($this->data);
        } catch (UnsupportedException $e) {
            Logger::error('could not resolve asset metadata implementation for ' . $this->type);
        }
    }

    public function __clone(): void
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
