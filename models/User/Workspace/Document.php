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

class Document extends AbstractWorkspace
{
    /**
     * @internal
     *
     * @var bool
     */
    protected bool $save = false;

    /**
     * @internal
     *
     * @var bool
     */
    protected bool $unpublish = false;

    /**
     * @param bool $save
     *
     * @return $this
     */
    public function setSave(bool $save): static
    {
        $this->save = $save;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSave(): bool
    {
        return $this->save;
    }

    /**
     * @param bool $unpublish
     *
     * @return $this
     */
    public function setUnpublish(bool $unpublish): static
    {
        $this->unpublish = $unpublish;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUnpublish(): bool
    {
        return $this->unpublish;
    }
}
