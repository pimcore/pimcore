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

namespace Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

use Pimcore\Model;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Listing\CallableFilterListingInterface;
use Pimcore\Model\Listing\CallableOrderListingInterface;
use Pimcore\Model\Listing\Traits\FilterListingTrait;

/**
 * @internal
 *
 * @method \Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing\Dao getDao()
 * @method Model\DataObject\ClassDefinition\CustomLayout|false current()
 * @method Model\DataObject\ClassDefinition\CustomLayout[] load()
 */
class Listing extends AbstractModel implements CallableFilterListingInterface, CallableOrderListingInterface
{
    use FilterListingTrait;

    /**
     * @var Model\DataObject\ClassDefinition\CustomLayout[]|null
     */
    protected ?array $layoutDefinitions = null;

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

    /**
     * @param Model\DataObject\ClassDefinition\CustomLayout[]|null $layoutDefinitions
     *
     * @return $this
     */
    public function setLayoutDefinitions(?array $layoutDefinitions): static
    {
        $this->layoutDefinitions = $layoutDefinitions;

        return $this;
    }

    /**
     * @return Model\DataObject\ClassDefinition\CustomLayout[]
     */
    public function getLayoutDefinitions(): array
    {
        if ($this->layoutDefinitions === null) {
            $this->layoutDefinitions = $this->load();
        }

        return $this->layoutDefinitions;
    }
}
