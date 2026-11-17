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

namespace Pimcore\Messenger;

/**
 * @internal
 */
class AssetUpdateTasksMessage
{
    /**
     * Not a promoted property on purpose: a message serialized before this property existed
     * has to fall back to the default when it is consumed after an update.
     */
    protected bool $dataChanged = false;

    /**
     * @param bool $dataChanged whether the task was queued because the binary of the asset changed
     *                          (as opposed to re-queueing an unchanged asset)
     */
    public function __construct(protected int $id, bool $dataChanged = false)
    {
        $this->dataChanged = $dataChanged;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isDataChanged(): bool
    {
        return $this->dataChanged;
    }
}
