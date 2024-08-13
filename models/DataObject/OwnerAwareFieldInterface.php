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

namespace Pimcore\Model\DataObject;

interface OwnerAwareFieldInterface
{
    /**
     *
     * @return $this;
     */
    public function _setOwner(mixed $owner): static;

    public function _setOwnerFieldname(?string $fieldname): static;

    public function _setOwnerLanguage(?string $language): static;

    public function _getOwner(): mixed;

    public function _getOwnerFieldname(): ?string;

    public function _getOwnerLanguage(): ?string;
}
