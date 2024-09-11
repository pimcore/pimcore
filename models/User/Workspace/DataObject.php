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

namespace Pimcore\Model\User\Workspace;

class DataObject extends AbstractWorkspace
{
    /**
     * @internal
     */
    protected bool $save = false;

    /**
     * @internal
     */
    protected bool $unpublish = false;

    /**
     * @internal
     */
    protected ?string $lEdit = null;

    /**
     * @internal
     */
    protected ?string $lView = null;

    /**
     * @internal
     */
    protected ?string $layouts = null;

    /**
     * @return $this
     */
    public function setSave(bool $save): static
    {
        $this->save = $save;

        return $this;
    }

    public function getSave(): bool
    {
        return $this->save;
    }

    /**
     * @return $this
     */
    public function setUnpublish(bool $unpublish): static
    {
        $this->unpublish = $unpublish;

        return $this;
    }

    public function getUnpublish(): bool
    {
        return $this->unpublish;
    }

    public function setLEdit(?string $lEdit): void
    {
        //@TODO - at the moment disallowing all languages is not possible - the empty lEdit value means that every language is allowed to edit...
        $this->lEdit = $lEdit;
    }

    public function getLEdit(): ?string
    {
        return $this->lEdit;
    }

    public function setLView(?string $lView): void
    {
        $this->lView = $lView;
    }

    public function getLView(): ?string
    {
        return $this->lView;
    }

    public function setLayouts(?string $layouts): void
    {
        $this->layouts = $layouts;
    }

    public function getLayouts(): ?string
    {
        return $this->layouts;
    }
}
