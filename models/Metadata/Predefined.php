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
    /**
     * @var string
     */
    protected string $id;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string|null
     */
    protected ?string $description;

    /**
     * @TODO if required?
     *
     * @var string
     */
    protected string $key;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @var string|null
     */
    protected ?string $targetSubtype;

    /**
     * @var mixed
     */
    protected mixed $data;

    /**
     * @var string|null
     */
    protected ?string $config;

    /**
     * @TODO if required?
     *
     * @var string
     */
    protected string $ctype;

    /**
     * @var string|null
     */
    protected ?string $language;

    /**
     * @var string|null
     */
    protected ?string $group;

    /**
     * @var int|null
     */
    protected ?int $creationDate;

    /**
     * @var int|null
     */
    protected ?int $modificationDate;

    /**
     * @param string $id
     *
     * @return self|null
     */
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
     * @param string $name
     * @param string $language
     *
     * @return self|null
     *
     * @throws \Exception
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

    /**
     * @return self
     */
    public static function create(): Predefined
    {
        $type = new self();
        $type->save();

        return $type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = str_replace('~', '---', $name);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    public function setData(string $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setLanguage(?string $language)
    {
        $this->language = $language;
    }

    /**
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setGroup(?string $group)
    {
        $this->group = $group;
    }

    /**
     * @return string|null
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setTargetSubtype(?string $targetSubtype)
    {
        $this->targetSubtype = $targetSubtype;
    }

    /**
     * @return string|null
     */
    public function getTargetSubtype(): ?string
    {
        return $this->targetSubtype;
    }

    /**
     * @return string|null
     */
    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function setConfig(?string $config)
    {
        $this->config = $config;
    }

    public function minimize()
    {
        try {
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            /** @var Model\Asset\MetaData\ClassDefinition\Data\Data $instance */
            $instance = $loader->build($this->type);
            $this->data = $instance->getDataFromEditMode($this->data);
        } catch (UnsupportedException $e) {
            Logger::error('could not resolve asset metadata implementation for ' . $this->type);
        }
    }

    public function expand()
    {
        try {
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            /** @var Model\Asset\MetaData\ClassDefinition\Data\Data $instance */
            $instance = $loader->build($this->type);
            $this->data = $instance->getDataForEditmode($this->data);
        } catch (UnsupportedException $e) {
            Logger::error('could not resolve asset metadata implementation for ' . $this->type);
        }
    }

    public function __clone()
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
