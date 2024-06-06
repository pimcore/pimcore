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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\ObjectVarTrait;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\Service;

class Video implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;
    use ObjectVarTrait;

    protected ?string $type = null;

    protected string|int|Asset|ElementDescriptor|null $data = null;

    protected string|int|Asset|ElementDescriptor|null $poster = null;

    protected ?string $title = null;

    protected ?string $description = null;

    public function setData(Asset|int|string|null $data): void
    {
        $this->data = $data;
        $this->markMeDirty();
    }

    public function getData(): Asset|int|string|null
    {
        return $this->data;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
        $this->markMeDirty();
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->markMeDirty();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPoster(Asset|int|string|null $poster): void
    {
        $this->poster = $poster;
        $this->markMeDirty();
    }

    public function getPoster(): Asset|int|string|null
    {
        return $this->poster;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
        $this->markMeDirty();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function __wakeup(): void
    {
        if ($this->data instanceof ElementDescriptor) {
            $asset = Service::getElementById($this->data->getType(), $this->data->getId());
            if ($asset instanceof Asset) {
                $this->data = $asset;
            } else {
                $this->data = null;
            }
        }
        if ($this->poster instanceof ElementDescriptor) {
            $asset = Service::getElementById($this->poster->getType(), $this->poster->getId());
            if ($asset instanceof Asset) {
                $this->poster = $asset;
            } else {
                $this->poster = null;
            }
        }
    }
}
