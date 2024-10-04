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

namespace Pimcore\Model\DataObject\Data;

use Exception;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;

/**
 * @method \Pimcore\Model\DataObject\Data\ObjectMetadata\Dao getDao()
 */
class ObjectMetadata extends Model\AbstractModel implements DataObject\OwnerAwareFieldInterface
{
    use DataObject\Traits\OwnerAwareFieldTrait;

    protected ?DataObject\AbstractObject $object = null;

    protected ?int $objectId = null;

    protected ?string $fieldname = null;

    protected array $columns = [];

    protected array $data = [];

    /**
     * @param Concrete|null $object
     */
    public function __construct(?string $fieldname, array $columns = [], DataObject\Concrete $object = null)
    {
        $this->fieldname = $fieldname;
        $this->columns = $columns;
        $this->setObject($object);
    }

    /**
     * @return $this
     */
    public function setObject(?DataObject\Concrete $object): static
    {
        $this->markMeDirty();

        if (!$object) {
            $this->setObjectId(null);

            return $this;
        }

        $this->objectId = $object->getId();

        return $this;
    }

    /**
     *
     * @return mixed|void
     *
     * @throws Exception
     */
    public function __call(string $method, array $args)
    {
        if (str_starts_with($method, 'get')) {
            $key = substr($method, 3, strlen($method) - 3);

            $idx = array_searchi($key, $this->columns);
            if ($idx !== false) {
                $correctedKey = $this->columns[$idx];

                return isset($this->data[$correctedKey]) ? $this->data[$correctedKey] : null;
            }

            throw new Exception("Requested data $key not available");
        }

        if (str_starts_with($method, 'set')) {
            $key = substr($method, 3, strlen($method) - 3);
            $idx = array_searchi($key, $this->columns);

            if ($idx !== false) {
                $correctedKey = $this->columns[$idx];
                $this->data[$correctedKey] = $args[0];
                $this->markMeDirty();
            } else {
                throw new Exception("Requested data $key not available");
            }
        }
    }

    public function save(DataObject\Concrete $object, string $ownertype, string $ownername, string $position, int $index): void
    {
        $this->getDao()->save($object, $ownertype, $ownername, $position, $index);
    }

    public function load(DataObject\Concrete $source, int $destinationId, string $fieldname, string $ownertype, string $ownername, string $position, int $index): ?ObjectMetadata
    {
        $return = $this->getDao()->load($source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index);
        $this->markMeDirty(false);

        return $return;
    }

    /**
     * @return $this
     */
    public function setFieldname(string $fieldname): static
    {
        $this->fieldname = $fieldname;
        $this->markMeDirty();

        return $this;
    }

    public function getFieldname(): ?string
    {
        return $this->fieldname;
    }

    public function getObject(): ?DataObject\Concrete
    {
        if ($this->getObjectId()) {
            $object = DataObject\Concrete::getById($this->getObjectId());
            if (!$object) {
                Logger::info('object ' . $this->getObjectId() . ' does not exist anymore');
            }

            return $object;
        }

        return null;
    }

    /**
     * @return $this
     */
    public function setElement(DataObject\Concrete $element): static
    {
        $this->markMeDirty();

        return $this->setObject($element);
    }

    public function getElement(): ?DataObject\Concrete
    {
        return $this->getObject();
    }

    /**
     * @return $this
     */
    public function setColumns(array $columns): static
    {
        $this->columns = $columns;
        $this->markMeDirty();

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
        $this->markMeDirty();
    }

    public function __toString(): string
    {
        return $this->getObject()->__toString();
    }

    public function getObjectId(): int
    {
        return (int) $this->objectId;
    }

    public function setObjectId(?int $objectId): void
    {
        $this->objectId = $objectId;
    }

    public function __wakeup(): void
    {
        if ($this->object) {
            $this->objectId = $this->object->getId();
        }
    }

    public function __sleep(): array
    {
        $finalVars = [];
        $blockedVars = ['object'];
        $vars = parent::__sleep();

        foreach ($vars as $value) {
            if (!in_array($value, $blockedVars)) {
                $finalVars[] = $value;
            }
        }

        return $finalVars;
    }
}
