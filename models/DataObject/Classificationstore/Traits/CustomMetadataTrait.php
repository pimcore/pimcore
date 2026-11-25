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

/**
 * The free key-value pairs of a configuration entity, held in the `customMetadata` column of all
 * four of them.
 *
 * Why a key has this column although it already carries a JSON blob: the pairs are a customer
 * namespace, validated for nothing - no key-name rules, no reserved prefixes, no type constraint -
 * while `definition` is Pimcore's own, whose members (fieldtype, name, title, locked, labelAlign,
 * indexed) core writes and reads. A pair named after one of them would shadow it and nothing would
 * refuse it. Keeping them apart also keeps one field name for the feature across all four levels,
 * where the other three have no `definition` to reuse.
 */
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
