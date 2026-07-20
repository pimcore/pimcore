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
