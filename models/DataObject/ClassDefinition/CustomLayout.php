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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Exception;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\DataObjectCustomLayoutEvents;
use Pimcore\Event\Model\DataObject\CustomLayoutEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Symfony\Component\Uid\UuidV4;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Dao getDao()
 * @method bool isWriteable()
 * @method string getWriteTarget()
 */
class CustomLayout extends Model\AbstractModel
{
    use DataObject\ClassDefinition\Helper\VarExport;
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?string $id = null;

    protected string $name = '';

    protected string $description = '';

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    protected int $userOwner;

    protected int $userModification;

    protected string $classId;

    protected ?Layout $layoutDefinitions = null;

    protected bool $default = false;

    public static function getById(string $id): ?CustomLayout
    {
        $cacheKey = 'customlayout_' . $id;

        try {
            $customLayout = RuntimeCache::get($cacheKey);
            if (!$customLayout) {
                throw new Exception('Custom Layout in registry is null');
            }
        } catch (Exception $e) {
            try {
                $customLayout = new self();
                $customLayout->getDao()->getById($id);
                RuntimeCache::set($cacheKey, $customLayout);
            } catch (Model\Exception\NotFoundException $e) {
                return null;
            }
        }

        return $customLayout;
    }

    /**
     * @throws Exception
     */
    public static function getByName(string $name): ?CustomLayout
    {
        $cacheKey = 'customlayout_' . $name;

        try {
            $customLayout = RuntimeCache::get($cacheKey);
            if (!$customLayout) {
                throw new Exception('Custom Layout in registry is null');
            }
        } catch (Exception $e) {
            try {
                $customLayout = new self();
                $customLayout->getDao()->getByName($name);
                RuntimeCache::set($cacheKey, $customLayout);
            } catch (Model\Exception\NotFoundException $e) {
                return null;
            }
        }

        return $customLayout;
    }

    /**
     * @throws Exception
     */
    public static function getByNameAndClassId(string $name, string $classId): ?CustomLayout
    {
        try {
            $customLayout = new self();
            $customLayout->getDao()->getByName($name);

            if ($customLayout->getClassId() != $classId) {
                throw new Model\Exception\NotFoundException('classId does not match');
            }

            return $customLayout;
        } catch (Model\Exception\NotFoundException $e) {
        }

        return null;
    }

    public function getFieldDefinition(string $field): Data|Layout|null
    {
        /**
         * @param string $key
         * @param Data|Layout $definition
         *
         * @return Data|null
         */
        $findElement = static function (string $key, Data|Layout $definition) use (&$findElement) {
            if ($definition->getName() === $key) {
                return $definition;
            }
            if (method_exists($definition, 'getChildren')) {
                foreach ($definition->getChildren() as $child) {
                    if ($childDefinition = $findElement($key, $child)) {
                        return $childDefinition;
                    }
                }
            }

            return null;
        };

        return $findElement($field, $this->getLayoutDefinitions());
    }

    public static function create(array $values = []): CustomLayout
    {
        $class = new self();
        $class->setValues($values);

        if (!$class->getId()) {
            $class->getDao()->getNewId();
        }

        return $class;
    }

    /**
     *
     * @throws DataObject\Exception\DefinitionWriteException
     */
    public function save(): void
    {
        if (!$this->isWriteable()) {
            throw new DataObject\Exception\DefinitionWriteException();
        }

        $isUpdate = $this->exists();

        if ($isUpdate) {
            $this->dispatchEvent(new CustomLayoutEvent($this), DataObjectCustomLayoutEvents::PRE_UPDATE);
        } else {
            $this->dispatchEvent(new CustomLayoutEvent($this), DataObjectCustomLayoutEvents::PRE_ADD);
        }

        $this->setModificationDate(time());

        $this->getDao()->save();

        // empty custom layout cache
        try {
            Cache::clearTag('customlayout_' . $this->getId());
        } catch (Exception $e) {
        }
    }

    /**
     * @internal
     */
    protected function getInfoDocBlock(): string
    {
        $cd = '/**' . "\n";

        if ($this->getDescription()) {
            $description = str_replace(['/**', '*/', '//'], '', $this->getDescription());
            $description = str_replace("\n", "\n* ", $description);

            $cd .= '* '.$description."\n";
        }
        $cd .= '*/';

        return $cd;
    }

    /**
     * @internal
     */
    public static function getIdentifier(string $classId): ?UuidV4
    {
        try {
            $customLayout = new self();

            return $customLayout->getDao()->getLatestIdentifier($classId);
        } catch (Exception $e) {
            Logger::error((string) $e);

            return null;
        }
    }

    /**
     * @throws DataObject\Exception\DefinitionWriteException
     */
    public function delete(): void
    {
        if (!$this->isWriteable()) {
            throw new DataObject\Exception\DefinitionWriteException();
        }

        // empty object cache
        try {
            Cache::clearTag('customlayout_' . $this->getId());
        } catch (Exception $e) {
        }

        // empty output cache
        try {
            Cache::clearTag('output');
        } catch (Exception $e) {
        }

        $this->getDao()->delete();
    }

    public function exists(): bool
    {
        if (is_null($this->getId())) {
            return false;
        }
        $name = $this->getDao()->getNameById($this->getId());

        return is_string($name);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getUserOwner(): int
    {
        return $this->userOwner;
    }

    public function getUserModification(): int
    {
        return $this->userModification;
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
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDefault(): bool
    {
        return $this->default;
    }

    /**
     * @return $this
     */
    public function setDefault(bool $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUserOwner(int $userOwner): static
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUserModification(int $userModification): static
    {
        $this->userModification = $userModification;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setLayoutDefinitions(?Layout $layoutDefinitions): void
    {
        $this->layoutDefinitions = $layoutDefinitions;
    }

    public function getLayoutDefinitions(): ?Layout
    {
        return $this->layoutDefinitions;
    }

    public function setClassId(string $classId): void
    {
        $this->classId = $classId;
    }

    public function getClassId(): string
    {
        return $this->classId;
    }
}
