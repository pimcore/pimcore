<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Extension\Document\Areabrick;

class EditableDialogBoxConfiguration implements \JsonSerializable
{
    /**
     * @var null|string
     */
    protected $id = null;

    /**
     * @var null|int
     */
    protected $width = 550;

    /**
     * @var null|int
     */
    protected $height = 370;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var bool
     */
    protected $reloadOnClose = false;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     *
     * @return $this
     */
    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @param int|null $width
     *
     * @return $this
     */
    public function setWidth(?int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @param int|null $height
     *
     * @return $this
     */
    public function setHeight(?int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     *
     * @return $this
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param array $item
     *
     * @return $this
     */
    public function addItem(array $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * @return bool
     */
    public function getReloadOnClose(): bool
    {
        return $this->reloadOnClose;
    }

    /**
     * @param bool $reloadOnClose
     *
     * @return $this
     */
    public function setReloadOnClose(bool $reloadOnClose): self
    {
        $this->reloadOnClose = $reloadOnClose;

        return $this;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
