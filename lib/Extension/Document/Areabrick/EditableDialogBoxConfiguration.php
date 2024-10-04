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

namespace Pimcore\Extension\Document\Areabrick;

use JsonSerializable;

class EditableDialogBoxConfiguration implements JsonSerializable
{
    protected ?string $id = null;

    protected ?int $width = 550;

    protected ?int $height = 370;

    protected array $items = [];

    protected bool $reloadOnClose = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @return $this
     */
    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @return $this
     */
    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return $this
     */
    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return $this
     */
    public function addItem(array $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    public function getReloadOnClose(): bool
    {
        return $this->reloadOnClose;
    }

    /**
     * @return $this
     */
    public function setReloadOnClose(bool $reloadOnClose): static
    {
        $this->reloadOnClose = $reloadOnClose;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
