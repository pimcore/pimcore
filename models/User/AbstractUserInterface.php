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

namespace Pimcore\Model\User;

use Exception;
use Pimcore\Model\ModelInterface;

/**
 * @method void setLastLoginDate()
 */
interface AbstractUserInterface extends ModelInterface
{
    public function getId(): ?int;

    /**
     * @return $this
     */
    public function setId(int $id): static;

    public function getParentId(): ?int;

    /**
     * @return $this
     */
    public function setParentId(int $parentId): static;

    public function getName(): ?string;

    /**
     * @return $this
     */
    public function setName(string $name): static;

    public function getType(): string;

    /**
     * @return $this
     *
     * @throws Exception
     */
    public function save(): static;

    /**
     * @throws Exception
     */
    public function delete(): void;

    /**
     * @return $this
     */
    public function setType(string $type): static;
}
