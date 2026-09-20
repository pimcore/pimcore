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
     * the properties are declared with default values (not promoted), so that messages queued before they were
     * introduced, which are unserialized without them, process the asset in any case as they did before
     */
    protected ?string $dataGeneration = null;

    protected bool $previewsOnly = false;

    /**
     * @param string|null $dataGeneration the data the task was created for (see
     *     \Pimcore\Model\Asset::getDataGeneration()): the task is skipped if the data was replaced or restored before
     *     the task is handled, or if its processing isn't pending anymore. Without it, the task processes the asset
     *     in any case.
     * @param bool $previewsOnly whether the task only generates the previews of the data, without processing it
     */
    public function __construct(protected int $id, ?string $dataGeneration = null, bool $previewsOnly = false)
    {
        $this->dataGeneration = $dataGeneration;
        $this->previewsOnly = $previewsOnly;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDataGeneration(): ?string
    {
        return $this->dataGeneration;
    }

    public function isPreviewsOnly(): bool
    {
        return $this->previewsOnly;
    }
}
