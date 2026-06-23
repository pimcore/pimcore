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

namespace Pimcore\Model\Listing\Traits;

trait OrderListingTrait
{
    /**
     * @var callable|null
     */
    protected $order;

    public function getOrder(): ?callable
    {
        return $this->order;
    }

    public function setOrder(?callable $order): static
    {
        $this->order = $order;

        return $this;
    }
}
