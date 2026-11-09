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

namespace Pimcore\Model\DataObject\Classificationstore\Traits;

trait CustomMetadataTrait
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $customMetadata = null;

    /**
     * Whether the pairs were loaded or explicitly set; a save writes the column only then.
     */
    protected bool $customMetadataInitialized = false;

    /**
     * @return array<string, mixed>|null
     */
    public function getCustomMetadata(): ?array
    {
        return $this->customMetadata;
    }

    /**
     * An empty map is absent: it is stored as NULL.
     *
     * @param array<string, mixed>|null $customMetadata
     */
    public function setCustomMetadata(?array $customMetadata): void
    {
        $this->customMetadata = $customMetadata === [] ? null : $customMetadata;
        $this->customMetadataInitialized = true;
    }

    /**
     * @internal
     */
    public function isCustomMetadataInitialized(): bool
    {
        return $this->customMetadataInitialized;
    }
}
