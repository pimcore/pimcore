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

namespace Pimcore\Model\DataObject;

interface OwnerAwareFieldInterface
{
    /**
     * @return $this
     */
    public function _setOwner(mixed $owner): static;

    public function _setOwnerFieldname(?string $fieldname): static;

    public function _setOwnerLanguage(?string $language): static;

    public function _getOwner(): mixed;

    public function _getOwnerFieldname(): ?string;

    public function _getOwnerLanguage(): ?string;
}
