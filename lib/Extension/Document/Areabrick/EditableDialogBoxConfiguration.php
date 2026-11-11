<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Extension\Document\Areabrick;

use JsonSerializable;

class EditableDialogBoxConfiguration implements JsonSerializable
{
    protected ?string $id = null;

    /**
     * @deprecated since 12.3, will be removed in 2027.1. The option has no effect in the new Studio UI.
     */
    protected ?int $width = 550;

    /**
     * @deprecated since 12.3, will be removed in 2027.1. The option has no effect in the new Studio UI.
     */
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

    /**
     * @deprecated since 12.3, will be removed in 2027.1. The option has no effect in the new Studio UI.
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @deprecated since 12.3, will be removed in 2027.1. The option has no effect in the new Studio UI.
     *
     * @return $this
     */
    public function setWidth(?int $width): static
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '12.3',
            'Setting the "width" option of %s is deprecated and will be removed in 2027.1. '
            . 'The option has no effect in the new Studio UI.',
            self::class
        );

        $this->width = $width;

        return $this;
    }

    /**
     * @deprecated since 12.3, will be removed in 2027.1. The option has no effect in the new Studio UI.
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @deprecated since 12.3, will be removed in 2027.1. The option has no effect in the new Studio UI.
     *
     * @return $this
     */
    public function setHeight(?int $height): static
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '12.3',
            'Setting the "height" option of %s is deprecated and will be removed in 2027.1. '
            . 'The option has no effect in the new Studio UI.',
            self::class
        );

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
