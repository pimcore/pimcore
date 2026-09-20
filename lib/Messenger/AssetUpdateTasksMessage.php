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
     * @param string|null $processingToken token of the data the task was created for (see
     *     \Pimcore\Model\Asset::getProcessingToken()): the task is skipped if the processing of this data isn't
     *     pending anymore when the task is handled, as the data was replaced or restored in the meantime. Without a
     *     token, the task processes the asset in any case.
     */
    public function __construct(protected int $id, protected ?string $processingToken = null)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProcessingToken(): ?string
    {
        return $this->processingToken;
    }
}
