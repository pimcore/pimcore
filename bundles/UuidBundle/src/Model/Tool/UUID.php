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

namespace Pimcore\Bundle\UuidBundle\Model\Tool;

use Exception;
use Pimcore;
use Pimcore\Bundle\UuidBundle\Model\Tool\UUID\Dao;
use Pimcore\Model;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @method Dao getDao()
 * @method void delete()
 * @method void save()
 */
final class UUID extends Model\AbstractModel
{
    /**
     * @internal
     *
     */
    protected int|string $itemId;

    /**
     * @internal
     *
     */
    protected string $type;

    /**
     * @internal
     *
     */
    protected string $uuid;

    /**
     * @internal
     *
     */
    protected string $instanceIdentifier;

    /**
     * @internal
     *
     */
    protected mixed $item = null;

    /**
     * @return $this
     */
    public function setInstanceIdentifier(string $instanceIdentifier): static
    {
        $this->instanceIdentifier = $instanceIdentifier;

        return $this;
    }

    public function getInstanceIdentifier(): string
    {
        return $this->instanceIdentifier;
    }

    /**
     * @internal
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setSystemInstanceIdentifier(): static
    {
        $instanceIdentifier = Pimcore::getKernel()->getContainer()->getParameter('pimcore_uuid.instance_identifier');
        if (empty($instanceIdentifier)) {
            throw new Exception('No instance identifier set in system config!');
        }
        $this->setInstanceIdentifier($instanceIdentifier);

        return $this;
    }

    /**
     * @return $this
     */
    public function setItemId(int|string $id): static
    {
        $this->itemId = $id;

        return $this;
    }

    public function getItemId(): int|string
    {
        return $this->itemId;
    }

    /**
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    public function createUuid(): string
    {
        if (!$this->getInstanceIdentifier()) {
            throw new Exception('No instance identifier specified.');
        }

        // namespace originally used from \Ramsey\Uuid\Uuid::NAMESPACE_DNS
        $namespace = Uid::fromString('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $uuid = Uid::v5($namespace, $this->getInstanceIdentifier() . '~' . $this->getType() . '~' . $this->getItemId());
        $this->uuid = $uuid->toRfc4122();

        if (!$this->getDao()->exists($this->uuid)) {
            $this->getDao()->create();
        }

        return $this->uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return $this
     */
    public function setItem(mixed $item): static
    {
        $this->setItemId($item->getId());

        if ($item instanceof Model\Element\ElementInterface) {
            $this->setType(Model\Element\Service::getElementType($item));
        } elseif ($item instanceof Model\DataObject\ClassDefinitionInterface) {
            $this->setType('class');
        }

        $this->item = $item;

        return $this;
    }

    /**
     *
     *
     * @throws Exception
     */
    public static function getByItem(mixed $item): UUID
    {
        $self = new self;
        $self->setSystemInstanceIdentifier();
        $self->setUuid($self->setItem($item)->createUuid());

        return $self;
    }

    public static function getByUuid(string $uuid): UUID
    {
        $self = new self;

        return $self->getDao()->getByUuid($uuid);
    }

    /**
     *
     *
     * @throws Exception
     */
    public static function create(mixed $item): static
    {
        $uuid = new static;
        $uuid->setSystemInstanceIdentifier()->setItem($item);
        $uuid->setUuid($uuid->createUuid());

        return $uuid;
    }
}
