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
    /**
     * @var string
     */
    protected string $id;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $description;

    /**
     * @var string|null
     */
    protected ?string $key;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @var string
     */
    protected string $data;

    /**
     * @var string
     */
    protected string $config;

    /**
     * @var string
     */
    protected string $ctype;

    /**
     * @var bool
     */
    protected bool $inheritable = false;

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
            $property = new self();
            $property->getDao()->getById($id);

            return $property;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param string $key
     *
     * @return self|null
     */
    public static function getByKey(string $key): ?Predefined
    {
        $cacheKey = 'property_predefined_' . $key;

        try {
            $property = \Pimcore\Cache\RuntimeCache::get($cacheKey);
            if (!$property) {
                throw new \Exception('Predefined property in registry is null');
            }
        } catch (\Exception $e) {
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
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
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
     * @param string $key
     *
     * @return $this
     */
    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

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
     * @return string
     */
    public function getConfig(): string
    {
        return $this->config;
    }

    /**
     * @param string $config
     *
     * @return $this
     */
    public function setConfig(string $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return string
     */
    public function getCtype(): string
    {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     *
     * @return $this
     */
    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @return bool
     */
    public function getInheritable(): bool
    {
        return $this->inheritable;
    }

    /**
     * @param bool $inheritable
     *
     * @return $this
     */
    public function setInheritable(bool $inheritable): static
    {
        $this->inheritable = (bool) $inheritable;

        return $this;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
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

    public function __clone()
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
