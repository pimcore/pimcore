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

namespace Pimcore\Model\Property;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method \Pimcore\Model\Property\Predefined\Dao getDao()
 * @method void delete()
 * @method void save()
 */
final class Predefined extends Model\AbstractModel
{
    protected ?string $id = null;

    protected ?string $name = null;

    protected ?string $description = null;

    protected ?string $key = null;

    protected ?string $type = null;

    protected ?string $data = null;

    protected ?string $config = null;

    protected ?string $ctype = null;

    protected bool $inheritable = false;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    public static function getById(string $id): ?Predefined
    {
        try {
            $property = new self();
            $property->getDao()->getById($id);

            return $property;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function getByKey(string $key): ?Predefined
    {
        $cacheKey = 'property_predefined_' . $key;

        try {
            $property = \Pimcore\Cache\RuntimeCache::get($cacheKey);
            if (!$property) {
                throw new Exception('Predefined property in registry is null');
            }
        } catch (Exception $e) {
            try {
                $property = new self();
                $property->getDao()->getByKey($key);
                \Pimcore\Cache\RuntimeCache::set($cacheKey, $property);
            } catch (Model\Exception\NotFoundException $e) {
                return null;
            }
        }

        return $property;
    }

    public static function create(): Predefined
    {
        $type = new self();
        $type->save();

        return $type;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * @return $this
     */
    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

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
    public function setData(?string $data): static
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

    public function getConfig(): ?string
    {
        return $this->config;
    }

    /**
     * @return $this
     */
    public function setConfig(string $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    /**
     * @return $this
     */
    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    public function getInheritable(): bool
    {
        return $this->inheritable;
    }

    /**
     * @return $this
     */
    public function setInheritable(bool $inheritable): static
    {
        $this->inheritable = $inheritable;

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

    public function __clone(): void
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
