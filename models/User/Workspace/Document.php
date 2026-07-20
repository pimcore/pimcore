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

namespace Pimcore\Model\User\Workspace;

class Document extends AbstractWorkspace
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
}
