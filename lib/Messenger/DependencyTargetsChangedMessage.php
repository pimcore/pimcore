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

use Pimcore\ValueObject\Element\DependencyTarget;

/**
 * @internal
 */
class DependencyTargetsChangedMessage
{
    /**
     * @param DependencyTarget[] $addedTargets
     * @param DependencyTarget[] $removedTargets
     */
    public function __construct(
        protected array $addedTargets,
        protected array $removedTargets,
    ) {
    }

    /**
     * @return DependencyTarget[]
     */
    public function getAddedTargets(): array
    {
        return $this->addedTargets;
    }

    /**
     * @return DependencyTarget[]
     */
    public function getRemovedTargets(): array
    {
        return $this->removedTargets;
    }
}
