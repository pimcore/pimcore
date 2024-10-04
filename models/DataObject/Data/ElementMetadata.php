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

/**
 * @method \Pimcore\Model\DataObject\Data\ElementMetadata\Dao getDao()
 */
class ElementMetadata extends Model\AbstractModel implements DataObject\OwnerAwareFieldInterface
{
    use DataObject\Traits\OwnerAwareFieldTrait;

    protected ?string $elementType = null;

    protected ?int $elementId = null;

    protected ?string $fieldname = null;

    protected array $columns = [];

    protected array $data = [];

    /**
     *
     * @throws Exception
     */
    public function __construct(?string $fieldname = null, array $columns = [], Model\Element\ElementInterface $element = null)
    {
        $this->fieldname = $fieldname;
        $this->columns = $columns;
        $this->setElement($element);
    }

    public function setElementTypeAndId(?string $elementType, ?int $elementId): void
    {
        $this->elementType = $elementType;
        $this->elementId = $elementId;
        $this->markMeDirty();
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
            $key = substr($method, 3);
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
        $element = $this->getElement();
        $type = Model\Element\Service::getElementType($element);
        $this->getDao()->save($object, $ownertype, $ownername, $position, $index, $type);
    }

    public function load(DataObject\Concrete $source, int $destinationId, string $fieldname, string $ownertype, string $ownername, string $position, int $index, string $destinationType): ?ElementMetadata
    {
        $return = $this->getDao()->load($source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index, $destinationType);
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

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    /**
     * @return $this
     */
    public function setElement(?Model\Element\ElementInterface $element): static
    {
        $this->markMeDirty();
        if (!$element) {
            $this->setElementTypeAndId(null, null);

            return $this;
        }

        $elementType = Model\Element\Service::getElementType($element);
        $elementId = $element->getId();
        $this->setElementTypeAndId($elementType, $elementId);

        return $this;
    }

    public function getElement(): ?Model\Element\ElementInterface
    {
        if ($this->getElementType() && $this->getElementId()) {
            $element = Model\Element\Service::getElementById($this->getElementType(), $this->getElementId());
            if (!$element) {
                Logger::info('element ' . $this->getElementType() . ' ' . $this->getElementId() . ' does not exist anymore');
            }

            return $element;
        }

        return null;
    }

    public function getElementType(): ?string
    {
        return $this->elementType;
    }

    public function getElementId(): ?int
    {
        return $this->elementId;
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
        return $this->getElement()->__toString();
    }
}
